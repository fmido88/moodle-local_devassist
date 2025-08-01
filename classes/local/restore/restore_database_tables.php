<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_devassist\local\restore;

use csv_import_reader;
use local_devassist\local\backup\backup_database_tables;
use xmldb_table;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/csvlib.class.php');
/**
 * Class restore_database_tables
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_database_tables extends restore_base {
    #[\Override]
    public static function get_unzip_location(): string {
        return backup_database_tables::get_temp_dir();
    }
    #[\Override]
    protected function restore() {
        global $DB;
        $path = self::get_unzip_location();
        $this->trace("restore from $path");
        $tr = $DB->start_delegated_transaction();
        try {
            // Delete newly installed tables that wasn't existed in the old
            // installation, this should not show any effect if the same version of
            // moodle or its component existed, if there is a difference the upgrade process
            // will run after restore process and these table will be installed again.
            $this->delete_new_tables();
            $this->restore_from_path($path);
        } catch (\Throwable $e) {
            $tr->rollback($e);
        }
        $tr->allow_commit();
        $this->trace("Completed.");
        $this->trace->finished();
    }

    /**
     * For upgrading processes, after testing when the database records restored
     * all versions return to its old values again and moodle need upgrading
     * if the new tables existed it cause exception in the upgrade process as some not
     * conditionally added.
     *
     * These tables will be renamed as {$tablename}_del to be deleted or restored later.
     * @return void
     */
    protected function delete_new_tables() {
        global $DB;
        $oldtables = $this->get_the_old_tables_list();
        if (!$oldtables) {
            debugging("The file tables.json was not provided in the tables list...", DEBUG_DEVELOPER);
            return;
        }
        $oldtables = array_values((array)$oldtables);
        $newtables = $DB->get_tables(false);
        foreach ($newtables as $table) {
            if (!in_array($table, $oldtables)) {
                $this->trace("The table $table should be deleted as it should be installed again in upgrade process...", 1);
                $this->trace("The table $table will be renamed to {$table}_del and you should delete it manually later...", 1);
                if (!isset($dbman)) {
                    $dbman = $DB->get_manager();
                }
                $xmldbtable = new xmldb_table($table);
                $dbman->rename_table($xmldbtable, $table . '_del');
            }
        }
    }

    /**
     * Get the old table list from tables.json file.
     * @param ?string $path
     */
    protected function get_the_old_tables_list($path = null) {
        if ($path === null) {
            $path = self::get_unzip_location();
        }

        if (is_file($path)) {
            $pathinfo = pathinfo($path);
            if ($pathinfo['basename'] === 'tables.json') {
                $tables = json_decode(file_get_contents($path), true);
                unlink($path);
                return $tables;
            } else {
                return null;
            }
        }

        $dir = dir($path);
        while (false !== ($entry = $dir->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $newpath = self::fix_directory_separator("$path/$entry");
            if (is_dir($newpath)) {
                if ($tables = $this->get_the_old_tables_list($newpath)) {
                    $dir->close();
                    return $tables;
                }
            } else if ($entry === 'tables.json') {
                $dir->close();
                return $this->get_the_old_tables_list($entry);
            }
        }
        $dir->close();
        return null;
    }

    /**
     * Restore tables by specifying the path of the csv files
     * or the directory contains it, this method recursively restore
     * all files in the directory.
     * @param mixed $path
     */
    protected function restore_from_path($path) {
        if (is_file($path)) {
            return $this->restore_single_table($path);
        }

        $dir = dir($path);
        while (false !== ($entry = $dir->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }
            $newpath = self::fix_directory_separator("$path/$entry");
            $this->restore_from_path($newpath);
        }
        $dir->close();
    }

    /**
     * Restore single table records by passing the path of
     * the csv file.
     *
     * @param string $filename
     * @return void
     */
    protected function restore_single_table($filename) {
        global $DB;
        $filename = self::fix_directory_separator($filename);
        $content = @file_get_contents($filename);

        $tablename = pathinfo($filename, PATHINFO_FILENAME);

        $dbman = $DB->get_manager();
        if (!$dbman->table_exists($tablename)) {
            $this->trace("The table $tablename not exist on this installation...");
            return;
        }

        if ($tablename == 'sessions') {
            $this->trace("Skipping the sessions table...");
            return;
        }

        $this->trace("Restoring table $tablename ...");
        // Process the CSV file.
        $importid = csv_import_reader::get_new_iid('devassist');
        $cir = new csv_import_reader($importid, 'devassist');

        $readcount = $cir->load_csv_content($content, 'utf-8', 'comma');
        $this->trace("Found $readcount records in $filename", 1);
        $cir->init();
        $columns = $cir->get_columns();

        $tablecolumns = $DB->get_columns($tablename);

        $inserted = 0;
        // Dropping the table records.
        $deleted = $DB->count_records($tablename);
        $DB->delete_records($tablename);
        while ($row = $cir->next()) {
            $this->parse_records($row, $columns, $tablecolumns);

            $DB->import_record($tablename, (object)$row);
            $inserted++;
        }

        $msg = "The table $tablename has been restored, ";
        $msg .= "The already existed records $deleted record has been deleted and $inserted records restored.";
        $this->trace($msg, 2);

        $cir->close();
        $cir->cleanup();
        if ($this->deleteoriginal) {
            @unlink($filename);
        }
    }


    /**
     * Parse a record for the form of associative array and fix
     * some values.
     * @param array $row the record as a line from the csv file.
     * @param array $columns the columns (first line in the csv file)
     * @param \database_column_info[] $tablecolumns The columns info
     * @return void
     */
    protected function parse_records(&$row, $columns, $tablecolumns) {
        $newrow = [];

        foreach ($row as $k => $v) {
            if (!isset($columns[$k])) {
                continue;
            }

            $columnname = $columns[$k];
            // Skip unknown table columns.
            if (!isset($tablecolumns[$columnname])) {
                continue;
            }

            $columninfo = $tablecolumns[$columnname];

            // Convert empty strings to null if column is not text-based.
            if ($v === '') {
                if (in_array($columninfo->meta_type, ['I', 'R', 'N', 'L'])) {
                    $v = $columninfo->not_null ? 0 : null;
                } else if (!$columninfo->not_null) {
                    $v = null;
                }
            }

            // Optionally cast types (e.g., integers) if needed.
            if (is_numeric($v) && in_array($columninfo->meta_type, ['I', 'N'])) {
                $v = (int) $v;
            }

            $newrow[$columnname] = $v;
        }

        $row = $newrow;
    }

    /**
     * Check if a record already existed with the same unique values.
     * @param string[][] $uniquecolumns The columns with unique keys or indices.
     * @param array $row the new record data.
     * @param string $tablename the table name.
     */
    protected function check_for_existed_record($uniquecolumns, $row, $tablename) {
        global $DB;
        // Try match by unique indexes first.
        $existingrecord = null;
        foreach ($uniquecolumns as $indexcols) {
            $conditions = [];
            $cancheck = true;
            foreach ($indexcols as $col) {
                if (!isset($row[$col])) {
                    $cancheck = false;
                    break;
                }
                $conditions[$col] = $row[$col];
            }

            if ($cancheck) {
                $existingrecord = $DB->get_record($tablename, $conditions, 'id');
                if ($existingrecord) {
                    break;
                }
            }
        }
        return $existingrecord;
    }

    /**
     * Get the unique columns for a table.
     * @param string $tablename
     * @param \database_column_info[] $columns
     * @return array
     */
    protected function get_unique_columns($tablename, $columns) {
        global $DB;
        $indexes = $DB->get_indexes($tablename);
        $uniques = [];

        foreach ($columns as $info) {
            if ($info->primary_key || $info->unique) {
                $uniques[] = [$info->name];
            }
        }

        foreach ($indexes as $index) {
            if (!empty($index['unique'])) {
                $uniques[] = $index['columns'];
            }
        }

        return $uniques;
    }
}

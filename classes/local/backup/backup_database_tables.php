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

namespace local_devassist\local\backup;

use core\exception\moodle_exception;
use csv_export_writer;

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->libdir}/csvlib.class.php");

/**
 * Class backup
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_database_tables extends backup_base {
    /**
     * Array of skipped tables mainly logs and sessions.
     * @var array
     */
    protected array $ignoredtables = [
        'logstore_standard_log',
        'log',
        'config_log',
        'sessions',
    ];
    /**
     * Add tables to ignore list.
     * @param string $table
     * @return void
     */
    public function add_to_ignore($table) {
        if (is_array($table)) {
            $this->ignoredtables = array_unique(array_merge($table, $this->ignoredtables));
        } else {
            $this->ignoredtables[] = $table;
        }
    }

    /**
     * Start backing up.
     * @throws moodle_exception
     * @return void
     */
    public function backup() {
        global $DB;
        $tables = $DB->get_tables(false);
        // Saving the tables list help in restore operation to check which table
        // was not existed in the old installation and delete it to prevent misbehave
        // in upgrade process.
        $this->save_tables_list($tables);

        $this->trace('Total ' . count($tables) . ' table found...');
        $backup = 0;
        foreach ($tables as $table) {
            if (in_array($table, $this->ignoredtables)) {
                $this->trace("Ignoring table $table ...", 1);
                continue;
            }

            $records = $DB->get_records($table);
            if (empty($records)) {
                $this->trace("The table $table is empty of records and will not be backed up...", 1);
                // No need to backup empty table.
                continue;
            }

            $bytes = $this->save_temp_table($table, $records);
            if ($bytes === false) {
                throw new moodle_exception("Cannot save the table $table in the temp directory.");
            }
            $this->trace("Saving the table $table", 1);
            $backup++;
        }
        $this->trace("Total $backup table(s) to be backed up...");
    }

    /**
     * Save the current list of tables to the temp directory.
     * @param array $tables
     * @return void
     */
    protected function save_tables_list($tables) {
        $tables = array_values((array)$tables);
        $json = json_encode($tables);
        $this->save_temp_file('tables.json', $json);
    }
    /**
     * Save a table records in a csv file.
     * @param string $tablename
     * @param \stdClass[] $records
     * @return bool|int
     */
    protected function save_temp_table($tablename, $records) {
        global $DB;

        // Add header row.
        $header = array_keys($DB->get_columns($tablename, false));

        $csv = new \csv_export_writer('comma', '"', 'application/download', true);
        $csv->set_filename($tablename . '.csv');
        $csv->add_data($header);

        foreach ($records as $row) {
            $rowdata = [];
            foreach ($header as $col) {
                $value = $row->$col ?? '';
                if ($value !== null) {
                    $value = (string)$value;
                }
                $rowdata[] = $value;
            }
            $csv->add_data($rowdata);
        }

        $content = $csv->print_csv_data(true);

        return $this->save_temp_file($tablename . '.csv', $content);
    }
}

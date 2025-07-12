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

use core\exception\coding_exception;
use local_devassist\form\restore as upload_form;
use local_devassist\local\backup_restore_base;
use local_devassist\local\zip_progress;
use zip_packer;

/**
 * Restore abstract class.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class restore_base extends backup_restore_base {
    /**
     * The restore form.
     * @var upload_form
     */
    protected upload_form $zipform;

    /**
     * The path of the zip file in the temp directory.
     * @var string
     */
    protected string $zipfilename;

    /**
     * Return the location at which the zip file should be extracted.
     * @return void
     */
    abstract public static function get_unzip_location(): string;

    /**
     * Proceed with the restoring process.
     * @return void
     */
    public function process() {
        $this->init();
        try {
            $this->extract();
            $this->restore();
        } catch (\Throwable $e) {
            $this->finished();
            throw $e;
        }

        if ($this->deleteoriginal) {
            @unlink($this->zipfilename);
        }

        $this->finished();
    }

    /**
     * This method called after extract() to continue restoration
     * if further steps needed.
     *
     * @return void
     */
    abstract protected function restore();

    /**
     * Extract the zip file content to the specific location.
     * @return void
     */
    protected function extract() {
        $content   = $this->zipform->get_file_content('zipfile');
        $tempdir   = static::get_temp_dir(true);
        $thingname = $this->get_supdir_name();
        $filename  = "{$tempdir}/{$thingname}.zip";
        $filename  = self::fix_directory_separator($filename);
        file_put_contents($filename, $content);

        $this->zipfilename = $filename;
        $zip               = new zip_packer();
        $progress          = null;

        if ($this->printprogress) {
            $progress = new zip_progress(-1, $this->trace);
        }
        $zip->extract_to_pathname($filename, static::get_unzip_location(), null, $progress);
    }

    /**
     * Set the restore form.
     * @param  upload_form $zipform
     * @return void
     */
    public function set_upload_form(upload_form $zipform) {
        $this->zipform = $zipform;
    }

    /**
     * Get the restore form.
     * @return upload_form
     */
    public function get_uploading_zip_form() {
        if (isset($this->zipform)) {
            return $this->zipform;
        }
        $this->zipform = new upload_form();

        return $this->zipform;
    }

    /**
     * Get the options of the restorations.
     * @return array{database_tables: string, files: string, plugins: string}
     */
    public static function get_options(): array {
        return [
            'plugins'           => get_string('restore_plugins', 'local_devassist'),
            'database_tables'   => get_string('restore_database_tables', 'local_devassist'),
            'files'             => get_string('restore_files', 'local_devassist'),
        ];
    }

    /**
     * Get the prober class instance according to the thing you want
     * to restore.
     *
     * @param  string           $thing
     * @throws coding_exception
     * @return restore_base
     */
    public static function get_instance($thing): self {
        switch ($thing) {
            case 'plugins':
                return new restore_plugins(true, true);

            case 'files':
                return new restore_files(true, true);

            case 'database_tables':
                return new restore_database_tables(true, true);

            default:
                throw new coding_exception("Unexpected thing parameter $thing passed to get_instance.");
        }
    }

    /**
     * Finishing the process and redirect to the admin page for possible upgrades.
     * @return never
     */
    protected function finished() {
        parent::finished();
        purge_caches();

        // This probably not work automatically but will display a redirection notice.
        redirect(new \moodle_url('/admin/index.php'));
    }
}

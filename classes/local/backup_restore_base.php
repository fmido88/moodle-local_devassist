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

namespace local_devassist\local;

use core\exception\coding_exception;
use core\output\progress_trace;
use core\output\progress_trace\html_list_progress_trace;
use core\output\progress_trace\null_progress_trace;
use core_php_time_limit;

/**
 * Class backup_restore_base.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class backup_restore_base {
    /**
     * The allowed options for backup and restoration.
     * @var array
     */
    public const ALLOWED = [
        'plugins',
        'files',
        'database_tables',
    ];

    /**
     * The temp directory name for the the current type of backup.
     * @var string
     */
    protected static $tempdir;

    /**
     * Base directory temp name
     * usually {tempdir}/local_devassist.
     * @var string
     */
    protected static $basetempdir;

    /**
     * Trace progress object.
     * @var progress_trace
     */
    protected progress_trace $trace;

    /**
     * Create an instance of backup class.
     * @param bool $deleteoriginal Delete the temp files after adding to the archive
     * @param bool $printprogress  If to print the progress of the backup process.
     */
    public function __construct(
        /** @var bool Delete the temp files after adding to the archive */
        protected bool $deleteoriginal,
        /** @var bool to print the progress of the backup process */
        protected bool $printprogress = true
    ) {
        $this->trace = $this->printprogress ? new html_list_progress_trace() : new null_progress_trace();
    }

    /**
     * Print a trace output.
     * @param string $text
     * @param int    $depth
     */
    protected function trace(string $text, $depth = 0) {
        flush();
        $this->trace->output($text, $depth);
        flush();
    }

    /**
     * Get the sub directory name inside temp directory.
     * @return string
     */
    protected static function get_supdir_name() {
        $class = static::class;
        $parts = explode('\\', $class);
        $name  = array_pop($parts);

        return explode('_', $name, 2)[1];
    }

    /**
     * Get the path to the temp directory for this backup type.
     * @param  bool   $base returning the base temp temp archive path.
     * @return string
     */
    protected static function get_temp_dir($base = false) {
        if (!$base && !empty(static::$tempdir)) {
            return static::$tempdir;
        } else if ($base && !empty(static::$basetempdir)) {
            return static::$basetempdir;
        }

        $tempname = 'local_devassist';

        if (!$base) {
            $tempname .= DIRECTORY_SEPARATOR . static::get_supdir_name();

            static::$tempdir = self::fix_directory_separator(make_temp_directory($tempname));

            return static::$tempdir;
        }

        static::$basetempdir = self::fix_directory_separator(make_temp_directory($tempname));

        return static::$basetempdir;
    }

    /**
     * Fix the directory separator in the given path.
     * @param  string $path
     * @return string
     */
    protected static function fix_directory_separator($path) {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Raise memory and time limits for long backup operations.
     * @return void
     */
    public static function raise_memory_and_time_limits() {
        // The backup process took sometime.
        core_php_time_limit::raise();
        // Not necessary.
        raise_memory_limit(MEMORY_HUGE);
    }

    /**
     * Get options for restore or backup.
     * @return string[]
     */
    abstract public static function get_options(): array;

    /**
     * Get instance of restoring or backing up class by the name of the
     * thing needed for restoring of backing up.
     * @param  string $thing
     * @return backup_restore_base
     */
    abstract public static function get_instance($thing): self;

    /**
     * Get the type of the current class (restore or backup).
     * @throws coding_exception
     * @return string
     */
    public function get_type() {
        $currentclass = get_class($this);
        $subclass     = str_replace(__NAMESPACE__, '', $currentclass);
        $type         = explode('\\', $subclass)[1];

        if (!in_array($type, ['restore', 'backup'])) {
            throw new coding_exception("Cannot extract correct type from class $currentclass");
        }

        return $type;
    }

    /**
     * Initialize the process by raising the memory and time limit
     * also entering the maintenance mode to prevent any other users
     * from accessing the site and change the data while backing up or restoring.
     * @return void
     */
    protected function init() {
        set_config('maintenance_enabled', 1);
        self::raise_memory_and_time_limits();
    }

    /**
     * Exit the maintenance mode and finish the trace.
     * @return void
     */
    protected function finished() {
        set_config('maintenance_enabled', 0);
        $this->trace->finished();
    }
}

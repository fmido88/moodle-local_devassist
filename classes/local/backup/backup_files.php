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

/**
 * Class backup_files.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_files extends backup_base {
    /**
     * Just an alias $CFG->filedir.
     * @var string
     */
    protected $filedir;

    /**
     * Override for the files directory.
     * @var string
     */
    protected static $tempdir;

    /**
     * Override to disallow delete original files parameter.
     * @param bool $printprogress
     */
    public function __construct(bool $printprogress) {
        global $CFG;

        if (isset($CFG->filedir)) {
            $this->filedir = $CFG->filedir;
        } else {
            $this->filedir = $CFG->dataroot . '/filedir';
        }
        self::$tempdir = $this->filedir;

        return parent::__construct(false, $printprogress);
    }

    #[\Override]
    public function backup() {
        global $DB;
        $selects = [];
        $params  = [];

        $i = 0;

        foreach ($this->excludedfileareas as $array) {
            $params["comp{$i}"] = $array[0];
            $sel                = "component != :comp{$i}";

            if (!empty($array[1])) {
                $params["fa{$i}"] = $array[1];
                $sel              = "({$sel} AND filearea != :fa{$i})";
            }
            $selects[] = $sel;
            $i++;
        }
        $select = implode(' OR ', $selects);
        $files  = $DB->get_records_select_menu('files', $select, $params, '', 'id, contenthash');

        $files = array_values(array_filter(array_map(function ($hash) {
            $path = $this->get_fullpath_from_hash($hash);

            if (!file_exists($path)) {
                return '';
            }

            return $path;
        }, $files)));

        foreach ($files as $path) {
            $this->tozipfiles[static::get_archive_path($path)] = $path;
        }
    }

    /**
     * Get the full directory to the stored file, including the path to the
     * filedir, and the directory which the file is actually in.
     *
     * @param  string $contenthash The content hash
     * @return string The full path to the content directory
     */
    protected function get_fullpath_from_hash($contenthash) {
        $l1   = $contenthash[0] . $contenthash[1];
        $l2   = $contenthash[2] . $contenthash[3];
        $ds   = DIRECTORY_SEPARATOR;
        $path = "{$this->filedir}{$ds}{$l1}{$ds}{$l2}{$ds}{$contenthash}";

        return self::fix_directory_separator($path);
    }

    /**
     * Get the path to the temp directory for this backup type.
     * @param mixed $base
     */
    protected static function get_temp_dir($base = false) {
        if ($base) {
            return parent::get_temp_dir($base);
        }

        if (!empty(static::$tempdir)) {
            return static::$tempdir;
        }

        global $CFG;
        static::$tempdir = $CFG->filedir ?? $CFG->dataroot . DIRECTORY_SEPARATOR . 'filedir';
        static::$tempdir = self::fix_directory_separator(static::$tempdir);

        return static::$tempdir;
    }
}

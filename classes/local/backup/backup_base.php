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

use core\exception\coding_exception;
use core\output\html_writer;
use local_devassist\local\backup_restore_base;
use local_devassist\local\zip_progress;
use moodle_url;

/**
 * Class backup_base.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class backup_base extends backup_restore_base {
    /**
     * List of files to be added to zip archive.
     * @var string[]
     */
    protected array $tozipfiles = [];

    #[\Override]
    public static function get_instance($thing): self {
        switch ($thing) {
            case 'plugins':
                return new backup_plugins(false, true);

            case 'files':
                return new backup_files(true);

            case 'database_tables':
                return new backup_database_tables(true, true);

            default:
                throw new coding_exception("Not allowed thing $thing passed tot eh constructor.");
        }
    }

    /**
     * Start the whole backup process.
     * @param  bool $printdownloadlink
     * @return void
     */
    public function process($printdownloadlink = true) {
        $this->init();
        try {
            $this->backup();
            $this->archive();
        } catch (\Throwable $e) {
            $this->finished();
            throw $e;
        }

        $this->trace('Backup completed...');
        $this->finished();

        if ($printdownloadlink) {
            $this->print_download_button(true);
        }
    }

    /**
     * Backup script.
     * @return void
     */
    abstract public function backup();

    /**
     * Save a single temp file
     * Should only be used inside backup() if needed.
     *
     * @param  string   $file    the file name with extension and subpath.
     * @param  string   $content the content of the file.
     * @return bool|int
     */
    protected function save_temp_file($file, $content) {
        $filename                = static::get_temp_dir() . DIRECTORY_SEPARATOR . $file;
        $this->tozipfiles[$file] = $filename;

        return file_put_contents($filename, $content);
    }

    /**
     * Format the path for the file inside the archive from the original file path.
     * @param  string $fullpath
     * @return string
     */
    protected static function get_archive_path($fullpath) {
        $base     = static::get_temp_dir();
        $base     = self::fix_directory_separator($base);
        $fullpath = self::fix_directory_separator($fullpath);

        return str_replace($base, '', $fullpath);
    }

    /**
     * Save all files in single zip file.
     * @return bool
     */
    protected function archive() {
        if (empty($this->tozipfiles)) {
            $msg = 'No files existed to be archived make sure the';
            $msg .= 'implantation of ::backup() correctly include the files';
            debugging($msg, DEBUG_DEVELOPER);

            return false;
        }

        $thing  = static::get_supdir_name();
        $packer = get_file_packer('application/zip');

        $tempdir  = static::get_temp_dir();
        $basetemp = self::get_temp_dir(true);

        $ds = DIRECTORY_SEPARATOR;

        $progress = null;

        if ($this->trace) {
            $progress = new zip_progress(count($this->tozipfiles), $this->trace);
            flush();
        }

        $done = $packer->archive_to_pathname($this->tozipfiles, "{$basetemp}{$ds}{$thing}.zip", false, $progress);

        if ($this->deleteoriginal && $done) {
            remove_dir($tempdir);
        }

        if (!$done) {
            $debug = debugging("Cannot save the archive file {$basetemp}{$ds}{$thing}.zip", DEBUG_DEVELOPER);

            if ($debug) {
                echo '<pre>';
                var_dump($this->tozipfiles, $basetemp);
                echo '<pre>';
            }
        }

        return $done;
    }

    /**
     * Print the download link after finishing the backup.
     * @param  bool $autoclick
     * @return void
     */
    public function print_download_button($autoclick = true) {
        global $PAGE;

        if (!$PAGE->has_set_url()) {
            $tempdir = static::get_temp_dir();
            $thing   = static::get_supdir_name();
            $ds      = DIRECTORY_SEPARATOR;
            $error   = "The file has been saved to {$tempdir}{$ds}{$thing}.zip and can't redirect as the page not set its url.";
            debugging($error, DEBUG_DEVELOPER);

            return;
        }

        $url = new moodle_url($PAGE->url, [
            'thing'    => static::get_supdir_name(),
            'download' => true,
            'sesskey'  => sesskey(),
        ]);

        $attributes = ['id' => 'download', 'class' => 'btn btn-secondary'];
        echo html_writer::link($url, get_string('download'), $attributes);

        if ($autoclick) {
            $code = <<<'JS'
                document.getElementById('download').click();
            JS;
            echo html_writer::script($code);
        }
    }

    /**
     * Begin the download of the backup zip archive file.
     * @param  bool  $deleteafterdownload
     * @return never
     */
    public static function download($deleteafterdownload = true) {
        $tempdir = static::get_temp_dir(true);
        $thing   = static::get_supdir_name();

        $filename = $tempdir . DIRECTORY_SEPARATOR . $thing . '.zip';
        $exists   = file_exists($filename);

        if (!$exists) {
            echo "The file $filename not exists";
            exit;
        }

        $filesize = filesize($filename);

        // Clean all output buffers because $PAGE may started buffers.
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $thing . '.zip"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . $filesize);

        readfile($filename);

        if ($deleteafterdownload) {
            unlink($filename);
        }
        exit;
    }

    #[\Override]
    public static function get_options(): array {
        $options = [];

        foreach (self::ALLOWED as $thing) {
            $options[$thing] = get_string("backup_$thing", 'local_devassist');
        }

        return $options;
    }
}

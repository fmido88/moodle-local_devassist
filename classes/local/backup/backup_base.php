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
use local_devassist\form\backup;
use local_devassist\local\backup_restore_base;
use local_devassist\local\zip_progress;

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

    /**
     * List of the archived zip files paths.
     * @var string[]
     */
    protected array $archivedzips = [];

    /**
     * list of pairs [component, filearea] to be excluded
     * if the file area is an empty string, all fileareas in
     * this component will be excluded.
     * @var string[][]
     */
    protected $excludedfileareas = [
            ['user', 'draft'],
            ['tool_recyclebin', ''],
            ['assignfeedback_editpdf', ''], // Large files.
        ];

    /**
     * The max size of files that should be packed in
     * a single zip file.
     * @var int
     */
    protected int $chunksize = -1;

    /**
     * Get the backup class according to the thing we want to backup
     * @param string $thing
     * @throws coding_exception
     * @return backup_database_tables|backup_files|backup_plugins
     */
    public static function get_instance($thing): self {
        switch ($thing) {
            case 'plugins':
                return new backup_plugins(false, true);

            case 'files':
                return new backup_files(true);

            case 'database_tables':
                return new backup_database_tables(true, true);

            default:
        }
        throw new coding_exception("Not allowed thing $thing passed tot eh constructor.");
    }

    /**
     * Add files belonging to a certain component and file area to the exclusion list.
     * @param  string $component the component in frankenstyle form.
     * @param  string $filearea  the file area, if not passed all files belonging to this
     *                           component will be excluded.
     * @return void
     */
    public function exclude_filearea($component, $filearea = '') {
        $component = clean_param($component, PARAM_COMPONENT);

        if (empty($component)) {
            debugging("Invalid component $component", DEBUG_DEVELOPER);

            return;
        }
        $filearea                  = clean_param($filearea, PARAM_AREA);
        $this->excludedfileareas[] = [$component, $filearea];
    }

    /**
     * Don't exclude any files and backup them all.
     * @return void
     */
    public function dont_exclude_fileareas() {
        $this->excludedfileareas = [];
    }

    /**
     * Set the file areas to be excluded, this could be array af arrays each of two elements, the first is the component
     * and the other is the filearea (optional) or string of entries each in a separate line each line contain component,filearea.
     * @param  array|string $newvalues
     * @return void
     */
    public function set_excluded_fileareas(array|string $newvalues) {
        if (is_string($newvalues)) {
            $newvalues = explode("\n", $newvalues);
            $newvalues = array_filter($newvalues);
            $newvalues = array_map(function ($line) {
                return array_map('trim', explode(',', $line));
            }, $newvalues);
        }
        $this->excludedfileareas = [];

        foreach ($newvalues as $array) {
            $array = array_values($array);
            $array = array_map('trim', $array);
            $this->exclude_filearea($array[0], $array[1] ?? '');
        }
    }

    /**
     * Get the excluded fileareas.
     * @param  bool $string either as string or as array
     *              {@see \local_devassist\local\backup\backup_base::set_excluded_fileareas()}
     * @return array<array>|string
     */
    public function get_excluded_fileareas($string = false): array|string {
        if (!$string) {
            return $this->excludedfileareas;
        }

        $output = array_map(function ($array) {
            return implode(',', $array);
        }, $this->excludedfileareas);

        return implode("\n", $output);
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
     * Zipping the files needed for backup in a single file.
     * @return bool
     */
    protected function archive(): bool {
        if (empty($this->tozipfiles)) {
            debugging('No files to archive.', DEBUG_DEVELOPER);

            return false;
        }

        $thing     = static::get_supdir_name();
        $packer    = get_file_packer('application/zip');
        $basetemp  = static::get_temp_dir(true);
        $ds        = DIRECTORY_SEPARATOR;
        $chunksize = $this->get_chunk_size();

        if ($chunksize === 0) {
            $chunksize = PHP_INT_MAX;
        }

        // Step 1: Estimate sizes and split into chunks.
        $currentbatch = [];
        $currentbytes = 0;
        $allbatches   = [];

        foreach ($this->tozipfiles as $relpath => $fullpath) {
            $size = filesize($fullpath);

            // If file alone exceeds limit, force it into its own zip.
            if ($size > $chunksize) {
                if (!empty($currentbatch)) {
                    $allbatches[] = $currentbatch;
                    $currentbatch = [];
                    $currentbytes = 0;
                }
                $allbatches[] = [$relpath => $fullpath];
                continue;
            }

            if ($currentbytes + $size > $chunksize) {
                $allbatches[] = $currentbatch;
                $currentbatch = [];
                $currentbytes = 0;
            }

            $currentbatch[$relpath] = $fullpath;
            $currentbytes += $size;
        }

        // Add last batch.
        if (!empty($currentbatch)) {
            $allbatches[] = $currentbatch;
        }

        // Step 2: Archive each batch.
        $this->archivedzips = [];

        foreach ($allbatches as $i => $batchfiles) {
            $zipname = self::format_chunk_name($i);
            $zippath = "{$basetemp}{$ds}{$zipname}";

            $this->trace("Creating archive: {$zipname}");

            $progress = $this->trace ? new zip_progress(count($batchfiles), $this->trace) : null;
            $done     = $packer->archive_to_pathname($batchfiles, $zippath, false, $progress);

            if ($done) {
                $this->archivedzips[] = $zippath;
            } else {
                debugging("Failed to create archive {$zippath}", DEBUG_DEVELOPER);
            }
        }

        $bundling = get_config('local_devassist', 'bundle_archive');

        if ($bundling && count($this->archivedzips) > 1) {
            $superzip   = $basetemp . DIRECTORY_SEPARATOR . $thing . '_bundle.zip';
            $batchfiles = [];

            foreach ($this->archivedzips as $zippath) {
                $batchfiles[basename($zippath)] = $zippath;
            }

            $progress = $this->trace ? new zip_progress(count($batchfiles), $this->trace) : null;
            $this->trace('Backing up in one zip file for download...');
            $done = $packer->archive_to_pathname($batchfiles, $superzip, true, $progress);

            foreach ($this->archivedzips as $zipfile) {
                @unlink($zipfile);
            }
        }

        if ($this->deleteoriginal) {
            remove_dir(static::get_temp_dir());
        }

        return !empty($this->archivedzips);
    }

    /**
     * Format chunk file name.
     * @param  string|int $i
     * @return string
     */
    protected static function format_chunk_name($i) {
        static $now = 0;

        if (!$now) {
            $clock = \core\di::get(\core\clock::class);
            $now   = $clock->time();
        }

        $thing = static::get_supdir_name();
        $index = str_pad($i, 4, '0', STR_PAD_LEFT);

        return "{$thing}_{$now}_{$index}.zip";
    }

    /**
     * Print download buttons for all zip parts.
     * @param  bool $autoclick
     * @return void
     */
    public function print_download_button($autoclick = true) {
        global $PAGE;

        if (!$PAGE->has_set_url()) {
            $tempdir = static::get_temp_dir();
            $thing   = static::get_supdir_name();
            debugging("Backup files saved to {$tempdir} but page URL is not set.", DEBUG_DEVELOPER);

            return;
        }

        // Find all zip files matching "thing_*.zip".
        $tempdir = static::get_temp_dir(true);
        $thing   = static::get_supdir_name();
        $files   = glob($tempdir . DIRECTORY_SEPARATOR . $thing . '_*.zip');

        if (empty($files)) {
            echo 'No archive zip files found.';
            $files = [];
        }

        // Should be one file only.
        foreach ($files as $i => $filepath) {
            $filename = basename($filepath);
            $url      = new \moodle_url($PAGE->url, [
                'downloadfile' => $filename,
                'thing'        => static::get_supdir_name(),
                'download'     => true,
                'sesskey'      => sesskey(),
            ]);

            $attributes = ['id' => 'download-' . $i, 'class' => 'btn btn-secondary m-1'];
            echo \html_writer::link($url, get_string('downloadfile', 'local_devassist', $filename), $attributes);
            echo '<br>';
        }

        backup::print_backup_list_link();

        if ($autoclick && count($files) > 0) {
            $count  = count($files);
            $script = <<<JS
                (function() {
                    const total = $count;
                    let i = 0;

                    function triggerNextDownload() {
                        if (i >= total) return;
                        const a = document.getElementById('download-' + i);
                        i++;
                        if (a) {
                            a.click();
                            return;
                        }
                        setTimeout(triggerNextDownload, 1000); // Delay to avoid browser blocking
                    }
                    window.addEventListener('beforeunload', function(e) {
                        e.preventDefault();
                        e.returnValue = 'Downloads are in progress.';
                    });
                    triggerNextDownload();
                })();
            JS;

            echo \html_writer::script($script);
        }
    }

    /**
     * Perform deleting of backup files.
     * @return void
     */
    public static function delete() {
        require_sesskey();

        $filename = required_param('deletefile', PARAM_FILE);
        $tempdir  = static::get_temp_dir(true);
        $filepath = $tempdir . DIRECTORY_SEPARATOR . $filename;
        @unlink($filepath);
    }

    /**
     * Begin download of a specific backup zip file.
     * @param  bool  $deleteafterdownload
     * @return never
     */
    public static function download($deleteafterdownload = false) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');
        require_sesskey();

        $filename = required_param('downloadfile', PARAM_FILE);
        $tempdir  = static::get_temp_dir(true);
        $filepath = $tempdir . DIRECTORY_SEPARATOR . $filename;

        self::try_enable_xsend();
        // Clean all output buffers because $PAGE may have started buffers.
        while (ob_get_level()) {
            ob_end_clean();
        }

        if ($deleteafterdownload) {
            send_temp_file($filepath, $filename);
        } else {
            send_file($filepath, $filename, null, 0, false, true);
        }

        exit;
    }

    private static function try_enable_xsend() {
        global $CFG;
        if (!empty($CFG->xsendfile)) {
            return;
        }

        $server = $_SERVER['SERVER_SOFTWARE'] ?? '';

        if (stripos($server, 'Apache') !== false) {
            if (function_exists('apache_get_modules') && in_array('mod_xsendfile', apache_get_modules())) {
                $CFG->xsendfile = 'X-Sendfile'; // Apache
            }
        } else if (stripos($server, 'nginx') !== false) {
            $CFG->xsendfile = 'X-Accel-Redirect'; // Nginx
            $CFG->xsendfilealiases = [
                '/tempdir/' => $CFG->dataroot,
            ];
        } else if (stripos($server, 'lighttpd') !== false) {
            $CFG->xsendfile = 'X-LIGHTTPD-send-file'; // Lighttpd
        }
    }
    #[\Override]
    public static function get_options(): array {
        $options = [];

        foreach (self::ALLOWED as $thing) {
            $options[$thing] = get_string("backup_$thing", 'local_devassist');
        }

        return $options;
    }

    /**
     * Get the chunk size.
     * @return int
     */
    public function get_chunk_size(): int {
        if ($this->chunksize >= 0) {
            return $this->chunksize;
        }
        $this->chunksize = self::get_max_upload_size();

        return $this->chunksize;
    }

    /**
     * Set the chunk size to a new value.
     * @param  string $chunksize
     * @return void
     */
    public function set_chunk_size($chunksize) {
        $chunksize = str_replace(' ', '', $chunksize);
        $bytes     = self::parse_size($chunksize);

        if ($bytes < 0) {
            debugging("Un-allowed chunk size: $chunksize");

            return;
        }
        $this->chunksize = $bytes;
    }

    /**
     * Get the size in bytes from given string returned from ini.
     * @param  string $size
     * @return int
     */
    protected static function parse_size($size): int {
        $unit  = strtolower(substr($size, -1));
        $value = (int)$size;

        return match ($unit) {
            'g'     => $value * 1024 * 1024 * 1024,
            'm'     => $value * 1024 * 1024,
            'k'     => $value * 1024,
            default => (int)$size
        };
    }

    /**
     * Get the max allowed upload file from the server config.
     * @param  mixed      $bytes
     * @return int|string
     */
    public static function get_max_upload_size($bytes = true) {
        $upload      = ini_get('upload_max_filesize');
        $uploadbytes = self::parse_size($upload);
        $post        = ini_get('post_max_size');
        $postbytes   = self::parse_size($post);

        if ($bytes) {
            return min($uploadbytes, $postbytes);
        }

        if ($uploadbytes < $postbytes) {
            return $upload;
        }

        return $post;
    }
}

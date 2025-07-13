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
        $chunksize = self::get_max_upload_size();

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
            $index   = str_pad($i, 3, '0', STR_PAD_LEFT);
            $zipname = "{$thing}_{$index}.zip";
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

        if (count($this->archivedzips) > 1) {
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

            return;
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

        if ($autoclick && count($files) > 0) {
            $count  = count($files);
            $script = <<<JS
                (function() {
                    const total = $count;
                    let i = 0;

                    function triggerNextDownload() {
                        if (i >= total) return;
                        const a = document.getElementById('download-' + i);
                        if (a) {
                            a.click();
                        }
                        i++;
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
     * Begin download of a specific backup zip file.
     * @param  bool  $deleteafterdownload
     * @return never
     */
    public static function download($deleteafterdownload = true) {
        require_sesskey();

        $filename = required_param('downloadfile', PARAM_FILE);
        $tempdir  = static::get_temp_dir(true);
        $filepath = $tempdir . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filepath)) {
            echo "The file $filename does not exist.";
            exit;
        }

        // Clean all output buffers because $PAGE may have started buffers.
        while (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));

        readfile($filepath);

        if ($deleteafterdownload) {
            unlink($filepath);
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

    /**
     * Get the size in bytes from given string returned from ini.
     * @param string $size
     * @return int
     */
    protected static function parse_size($size) {
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
     * @return int
     */
    protected static function get_max_upload_size() {
        return min(
            static::parse_size(ini_get('upload_max_filesize')),
            static::parse_size(ini_get('post_max_size'))
        );
    }
}

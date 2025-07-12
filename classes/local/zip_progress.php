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

use core\output\progress_trace;
use file_progress;

defined('MOODLE_INTERNAL') || die();

require_once("{$CFG->libdir}/filestorage/file_progress.php");

/**
 * For tracing the progress in a zip archive.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class zip_progress implements file_progress {
    /**
     * How much is done.
     * @var int
     */
    protected int $done = 0;

    /**
     * The last percentage progress.
     * @var float
     */
    protected float $lastpercent = 0;

    /**
     * If the operation finished.
     * @var bool
     */
    protected bool $finished = false;

    /**
     * If the operation started.
     * @var bool
     */
    protected bool $init = false;

    /**
     * A file progress implantation to trace the progress
     * of a zip file.
     * @param int            $totalcount
     * @param progress_trace $trace
     */
    public function __construct(
        /** @var int The total count of files */
        protected int $totalcount,
        /** @var progress_trace the progress trace used to print output. */
        public progress_trace $trace
    ) {
    }

    #[\Override]
    public function progress($progress = file_progress::INDETERMINATE, $max = file_progress::INDETERMINATE) {
        if ($this->finished) {
            return;
        }

        if ($this->done == 0 && !$this->init) {
            $this->trace->output('Start packing the archive file');
            $this->init = true;
        }

        if ($max == file_progress::INDETERMINATE) {
            $max = $this->totalcount * 2;
        }

        if ($progress == file_progress::INDETERMINATE) {
            $progress = $this->done;
        }

        $this->done++;

        $percent = min(ceil($progress / $max * 10000) / 100, 100);

        if ($percent < 100.00 && $percent <= $this->lastpercent + 2) {
            return;
        }

        if ($percent == $this->lastpercent) {
            return;
        }

        $this->lastpercent = $percent;
        $this->trace->output(str_repeat('=', floor($percent / 2.5)) . $percent . '%', 1);

        if ($percent == 100) {
            $this->trace->output('Saving the zip file, it will be finished in a moment. Don\'t close or refresh the window...');
            $this->finished = true;
        }
    }
}

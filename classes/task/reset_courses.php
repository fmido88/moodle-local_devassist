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

namespace local_devassist\task;

use core\context\course;
use core\output\progress_trace\text_progress_trace;
use core\task\adhoc_task;

/**
 * Class reset_courses
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_courses extends adhoc_task {
    public function execute() {

        $trace = new text_progress_trace;
        $data = (object)$this->get_custom_data();

        $componentstr = get_string('resetcomponent');
        $taskstr      = get_string('resettask');
        $statusstr    = get_string('resetstatus');

        $courses = $data->courses;
        foreach ($courses as $courseid) {
            $data->id = $data->courseid = $courseid;

            $coursecontext = course::instance($courseid);
            $course        = get_course($courseid);
            $coursename    = format_string($course->fullname, true, ['context' => $coursecontext]);

            if (!has_capability('moodle/course:reset', $coursecontext)) {
                $error = get_string('nopermissions', 'error') . ' ' . $coursename;
                $trace->output($error);
                $trace->output("\n");
                continue;
            }

            $data->reset_start_date_old = $course->startdate;
            $data->reset_end_date_old   = $course->enddate;

            $trace->output($coursename);
            $status = reset_course_userdata($data);
            foreach ($status as $item) {
                $trace->output($componentstr . ': ' . $item['component'], 1);
                $trace->output($taskstr . ': ' . $item['item'], 2);
                $trace->output($statusstr . ': ' . ($item['error'] === false) ? get_string('statusok') : $item['error'], 2);
            }
            $trace->output("\n");
        }
        $trace->finished();
    }
}

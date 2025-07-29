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

/**
 * TODO describe file resetcourses.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login(null, false);

$url = new moodle_url('/local/devassist/resetcourses.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading($SITE->fullname);

// Form definition checks the capability to reset for each course.
$form = new \local_devassist\form\reset_courses();

if ($form->is_cancelled()) {
    redirect($url);
}

if ($data = $form->get_data()) {
    $statuses = [];

    foreach ($courses as $courseid) {
        $data->id = $data->courseid = $courseid;

        $coursecontext = context_course::instance($courseid);
        $course        = get_course($courseid);
        $coursename    = format_string($course->fullname, true, ['context' => $coursecontext]);

        if (!has_capability('moodle/course:reset', $coursecontext)) {
            $error      = get_string('nopermissions', 'error') . ' ' . $coursename;
            $statuses[] = [
                'status'     => [],
                'coursename' => $OUTPUT->notification($error, core\notification::ERROR, false),
            ];
            continue;
        }

        $data->reset_start_date_old = $course->startdate;
        $data->reset_end_date_old   = $course->enddate;

        $statuses[] = [
            'status'     => reset_course_userdata($data),
            'coursename' => $coursename,
        ];
    }
}

echo $OUTPUT->header();

if (!empty($statuses)) {
    $table        = new html_table();
    $table->head  = [
        get_string('resetcomponent'),
        get_string('resettask'),
        get_string('resetstatus'),
    ];
    $table->size  = ['20%', '40%', '40%'];
    $table->align = ['left', 'left', 'left'];
    $table->width = '80%';

    foreach ($statuses as $status) {
        $data = [];

        if (!empty($status['status'])) {
            echo $OUTPUT->heading($status['coursename'], 3);
        } else {
            echo $status['coursename'];
        }

        foreach ($status['status'] as $item) {
            $line   = [];
            $line[] = $item['component'];
            $line[] = $item['item'];
            $line[] = ($item['error'] === false) ? get_string('statusok')
                      : '<div class="notifyproblem">' . $item['error'] . '</div>';
            $data[] = $line;
        }

        if (!empty($data)) {
            $table->data = $data;
            echo html_writer::table($table);
        }

        echo html_writer::empty_tag('hr');
    }

    echo html_writer::link($url, get_string('continue'), ['class' => 'btn btn-primary']);
    echo $OUTPUT->footer();
    die();
}

$form->load_defaults();

echo $OUTPUT->heading(get_string('resetcourses', 'local_devassist'));

$form->display();

echo $OUTPUT->footer();

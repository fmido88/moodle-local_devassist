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
use local_devassist\task\reset_courses;

define('NO_OUTPUT_BUFFER', true);
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
    $task = new reset_courses();
    $task->set_custom_data($data);
    $task->set_userid($USER->id);

    $result = core\task\manager::queue_adhoc_task($task);

    echo '<pre>';
    var_dump($result);
    echo '</pre>';
    die;
    $msg = get_string('restcoursesqueued', 'local_devassist');
    redirect($url, $msg);
}

echo $OUTPUT->header();

$form->load_defaults();

echo $OUTPUT->heading(get_string('resetcourses', 'local_devassist'));

$form->display();

echo $OUTPUT->footer();

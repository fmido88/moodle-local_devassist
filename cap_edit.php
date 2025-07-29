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
 * Edit capabilities
 *
 * @package    local_devassist
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_admin();

local_devassist\common::check_developer_tools_enabled();

$url = new moodle_url('/local/devassist/cap_edit.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');

$title = get_string('cap_edit', 'local_devassist');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$mform = new local_devassist\form\capabilities();

if ($mform->is_cancelled()) {
    redirect($url);
}

if ($data = $mform->get_data()) {
    if (!empty($data->submitbutton)) {
        $parser = new local_devassist\capabilities_edit($data);
        $parser->rewrite_new_file();
        exit;
    }
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();

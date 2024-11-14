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
 * Find missing strings.
 *
 * @package    local_devassist
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__.'/locallib.php');

require_admin();

$url = new moodle_url('/local/devassist/missing_strings.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$title = get_string('missing_lang_strings', 'local_devassist');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$PAGE->set_pagelayout('admin');

$data = [];
if ($type = optional_param('type', null, PARAM_PLUGIN)) {
    $data['type'] = $type;
    $data[$type] = optional_param($type, null, PARAM_PLUGIN);
}

$data['fullcomponent'] = optional_param('fullcomponent', null, PARAM_COMPONENT);
$data['lang'] = optional_param('lang', 'en', PARAM_ALPHA);

$mform = new local_devassist\form\missing_strings(null, $data);
if ($mform->is_cancelled()) {
    redirect($url);
}

if ($result = $mform->add_string_file()) {
    if ($result === -1) {
        $msg = get_string('cannot_write_file', 'local_devassist');
        $type = 'error';
    } else {
        $msg = get_string('strings_added_success', 'local_devassist', $result);
        $type = 'success';
    }
    redirect(new moodle_url('/local/devassist/missing_strings.php'), $msg, null, $type);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();

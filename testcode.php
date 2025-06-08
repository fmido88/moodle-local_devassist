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
 * A page to evaluate PHP code.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once('locallib.php');

require_admin();

$url = new moodle_url('/local/devassist/testcode.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading('Evaluate php code');
$PAGE->requires->css(new moodle_url('/local/devassist/codemirror.css'));
$code = optional_param('code', null, PARAM_RAW);

try {
    ob_start();
    eval($code);
    $output = ob_get_clean();
} catch (Throwable $e) {
    $output = local_devassist_test_exception_handler($e);
}

echo $OUTPUT->header();

$mform = new MoodleQuickForm('evaluate', 'post', $url, '', ['class' => 'full-width-labels']);

$mform->addElement('textarea', 'code', 'Code');
if ($code) {
    $mform->setDefault('code', $code);
}

$mform->addElement('submit', 'eval', 'Evaluate');

$mform->display();

$PAGE->requires->js_call_amd('local_devassist/editor', 'init', ['php']);



echo $OUTPUT->box($output);

echo $OUTPUT->footer();

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
 * TODO describe file testcode
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
use local_devassist\common;
require_once('../../config.php');
require_once('locallib.php');
require_once("$CFG->libdir/formslib.php");
require_admin();

$url = new moodle_url('/local/devassist/editpluginserverfiles.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$PAGE->set_heading('Edit plugin file');
$PAGE->requires->css(new moodle_url('/local/devassist/codemirror.css'));

$file = optional_param('file', null, PARAM_PATH);
$type = optional_param('type', null, PARAM_PLUGIN);
$code = optional_param('code', null, PARAM_RAW);

$cancel = optional_param('cancel', false, PARAM_BOOL);
if ($cancel) {
    redirect($url);
}

if ($file && $code && confirm_sesskey()) {
    $temp = make_backup_temp_directory('local_devassist');

    $file = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $file);

    ['filename' => $filename, 'extension' => $ext, 'dirname' => $basedir] = pathinfo($file);

    $backupdir = $temp . DIRECTORY_SEPARATOR . $basedir;

    check_dir_exists($backupdir);

    $backupfilename = $backupdir . DIRECTORY_SEPARATOR . $filename . '.backup_' . time() . '.' . $ext;
    $oldcontent = file_get_contents($file);
    // Save backup.
    file_put_contents($backupfilename, $oldcontent);

    // Save new content.
    file_put_contents($CFG->dirroot . DIRECTORY_SEPARATOR . $file, $code);

    redirect($url);
}

$mform = new MoodleQuickForm('editpluginsfiles', 'post', $url, '', ['class' => 'full-width-labels']);

common::add_plugins_selection_options($mform);
$submit = 'select';

if ($type) {
    $mform->setConstant('type', $type);
    $mform->hardFreeze('type');

    $plugin = required_param($type, PARAM_PLUGIN);
    $mform->setConstant($type, $plugin);
    $mform->hardFreeze($plugin);

    $component = "{$type}_{$plugin}";
    $mform->addElement('hidden', 'component', $component);

    if (!$file) {
        $pman = \core_plugin_manager::instance();
        $info = $pman->get_plugin_info($component);
    
        local_devassist_list_dir_files($info->rootdir, $options);
    
        $options = array_combine($options, $options);
        $mform->addElement('select', 'file', get_string('file'), $options);
    }
}

if ($file) {
    $mform->addElement('text', 'file_name', get_string('file'));
    $mform->setType('file_name', PARAM_TEXT);
    $mform->setConstant('file_name', $file);
    $mform->hardFreeze('file_name');

    $mform->addElement('hidden', 'file');
    $mform->setType('file', PARAM_TEXT);
    $mform->setConstant('file', $file);

    $mform->addElement('textarea', 'code', 'Code');
    $mform->setDefault('code', file_get_contents($file));
    $submit = 'savechanges';

    $PAGE->requires->js_call_amd('local_devassist/editor', 'init', [pathinfo($file, PATHINFO_EXTENSION)]);
}

$mform->addElement('hidden', 'sesskey', sesskey());

$mform->addElement('submit', $submit, get_string($submit));
$mform->addElement('cancel');

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();

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
 * Script to backup all additional plugins in moodle installation
 *
 * @package    local_devassist
 * @copyright  2024 MohammadFarouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once(__DIR__.'/locallib.php');
require_admin();

$url = new moodle_url('/local/devassist/plugins_backup.php', []);

$confirm = optional_param('confirm', false, PARAM_BOOL);
$download = optional_param('download', false, PARAM_BOOL);

if ($download && confirm_sesskey()) {
    $tempdir = make_temp_directory('local_devassist');
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="plugins.zip"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($tempdir . '/plugins.zip'));
    readfile($tempdir . '/plugins.zip');
    unlink($tempdir . '/plugins.zip');
    exit;
}

if ($confirm && confirm_sesskey()) {

    $pluginman = core_plugin_manager::instance();

    $plugininfo = $pluginman->get_plugins();

    $count = 0;
    $files = [];
    $tempdir = make_temp_directory('local_devassist');
    foreach ($plugininfo as $type => $plugins) {
        mtrace('start copying '. $type . "<br>");
        $typecopied = 0;
        foreach ($plugins as $name => $plugin) {
            if ($plugin->get_status() === core_plugin_manager::PLUGIN_STATUS_MISSING) {
                continue;
            }
            if ($plugin->is_standard()) {
                continue;
            }
            mtrace($count++ . ": ");
            mtrace('start copying '. $plugin->name . "<br>");
            $subdir = str_replace($CFG->dirroot, '', $plugin->rootdir);

            $dir = $tempdir . '/plugins' . $subdir;
            local_devassist_copyr($plugin->rootdir, $dir, $files);
            mtrace($plugin->name . ' has been copied to ' . $dir . "<br>");
            $typecopied++;
        }
        mtrace($typecopied . " plugins has been copied of type " . $type . "<br>");
    }
    mtrace($count . ' plugins has been copied.' . "<br>");
    mtrace('All plugins copied successfully to ' . $tempdir . '/plugins' . "<br>");

    $packer = get_file_packer('application/zip');

    $packer->archive_to_pathname($files, $tempdir . '/plugins.zip');
    remove_dir($tempdir . '/plugins');

    $url->params(['download' => true, 'sesskey' => sesskey()]);
    echo html_writer::link($url, get_string('download'), ['id' => 'download']);
    $code = <<<JS
        document.getElementById('download').click();
    JS;
    echo html_writer::script($code);
    exit;
}

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');

$title = get_string('plugins_backup', 'local_devassist');
$PAGE->set_heading($title);
$PAGE->set_title($title);

echo $OUTPUT->header();

echo $OUTPUT->heading($title);

$mform = new MoodleQuickForm('pluginsbackup', 'post', $url, '_blank', ['class' => 'box py-3 modal-content']);

$confirm = $OUTPUT->box(get_string('plugin_backup_confirm', 'local_devassist'), 'box py-3 modal-body');

$mform->addElement('html', $confirm);
$mform->addElement('submit', 'confirm', get_string('confirm'));

$mform->addElement('hidden', 'sesskey');
$mform->setType('sesskey', PARAM_ALPHANUMEXT);
$mform->setDefault('sesskey', sesskey());

$mform->display();

echo $OUTPUT->footer();

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
 * TODO describe file backuplist
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_admin();

$download = optional_param('downloadfile', null, PARAM_FILE);
$delete = optional_param('deletefile', null, PARAM_FILE);

$url = new moodle_url('/local/devassist/backuplist.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$title = get_string('backups', 'local_devassist');
$PAGE->set_heading($title);
$PAGE->set_title($title);
$PAGE->set_pagelayout('admin');

if ($download && confirm_sesskey()) {
    local_devassist\local\backup\backup_base::download(false);
}

if ($delete && confirm_sesskey()) {
    local_devassist\local\backup\backup_base::delete();
    redirect($url);
}

// Find all zip files matching "thing_*.zip".
$tempdir = make_temp_directory('local_devassist');
$things  = local_devassist\local\backup\backup_base::get_options();

$table = new html_table();
$table->head = [
    'size'       => get_string('filesize', 'local_devassist'),
    'name'       => get_string('filename', 'local_devassist'),
    'backuptime' => get_string('backuptime', 'local_devassist'),
    'download'   => get_string('download', 'local_devassist'),
    'delete'     => get_string('delete', 'local_devassist'),
];

$formatsize = function(int $bytes) {
    $kilobytes = $bytes / 1024;
    $megabytes = $kilobytes / 1024;
    if ($megabytes > 1) {
        return format_float($megabytes, 2) . ' MB';
    }
    if ($kilobytes > 1) {
        return format_float($kilobytes, 2) . ' KB';
    }
    return $bytes . ' B';
};

foreach (array_keys($things) as $thing) {
    $files = glob($tempdir . DIRECTORY_SEPARATOR . $thing . '_*.zip');
    if ($files === false) {
        continue;
    }

    foreach ($files as $file) {
        $name = basename($file);
        $download = new moodle_url($url, ['downloadfile' => $name, 'sesskey' => sesskey()]);
        $download = html_writer::link($download, get_string('download', 'local_devassist'), ['class' => 'btn btn-secondary']);

        $delete = new moodle_url($url, ['deletefile' => $name, 'sesskey' => sesskey()]);
        $delete = html_writer::link($delete, get_string('delete', 'local_devassist'), ['class' => 'btn btn-secondary']);
        $table->data[] = [
            'size'       => $formatsize(filesize($file)),
            'name'       => $name,
            'backuptime' => userdate(filemtime($file)),
            'download'   => $download,
            'delete'     => $delete,
        ];
    }
}

usort($table->data, function($a, $b) {
    $order = $a['backuptime'] <=> $b['backuptime'];
    if ($order === 0) {
        $order = strcasecmp($a['name'], $b['name']);
    }
    return $order;
});

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('backupfileslist', 'local_devassist'));

if (!empty($table->data)) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('nobackupfiles', 'local_devassist'), null, false);
}

echo $OUTPUT->footer();

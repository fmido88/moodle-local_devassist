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
 * Script to modify lang files and resort it alphabetically
 *
 * @package    local_devassist
 * @copyright  2024 MohammadFarouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_admin();

$url = new moodle_url('/local/devassist/lang_sorter.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');

$title = get_string('lang_sorter', 'local_devassist');
$PAGE->set_heading($title);
$PAGE->set_title($title);

$mform = new local_devassist\form\lang_sorter();

if ($data = $mform->get_data()) {
    $type = $data->type;
    $component = $data->$type;
    $spaces = (bool)($data->spaces ?? false);

    $pluginman = core_plugin_manager::instance();
    $info = $pluginman->get_plugin_info($type . "_" . $component);
    $dirpath = $info->rootdir . '/lang';
    $entries = scandir($dirpath);
    $files = [];
    foreach ($entries as $entry) {
        if (in_array($entry, ['.', '..'])) {
            continue;
        }

        $filepath = $dirpath . "/" . $entry . "/" . "{$type}_{$component}.php";
        if (!file_exists($filepath)) {
            continue;
        }

        $string = [];
        include($filepath);
        $content = file_get_contents($filepath);

        $position = strpos($content, '$string');
        $heading = trim(substr($content, 0, $position));

        $heading = str_replace(PHP_EOL, "\n", $heading);
        unset($content);

        ksort($string, SORT_STRING);

        $file = '';
        $firstletter = 0;
        foreach ($string as $key => $value) {
            if ($spaces && stripos($key, $firstletter) !== 0) {
                $file .= "\n" . "\n";
                $firstletter = $key[0];
            }
            $file .= '$string[\''.$key.'\'] = \''.str_replace('\'', '\\\'', $value).'\';' . "" . "\n";
        }

        $file = str_replace(["\n ", " \n", " ". PHP_EOL, PHP_EOL . " ", PHP_EOL], "\n", $file);
        $file = $heading . "\n" . $file;
        if (file_put_contents($filepath, $file)) {
            $files[] = $filepath;
        }
    }

    if (!empty($files)) {
        $message = get_string('string_files_success', 'local_devassist');
        $message .= '<ul>';
        foreach ($files as $file) {
            $message .= '<li>' . $file . '</li>';
        }
        $message .= '</ul>';
        $type = 'success';
    } else {
        $message = get_string('string_files_nothing', 'local_devassist', $type . "_" . $component);
        $type = 'error';
    }
}

echo $OUTPUT->header();

if (!empty($message)) {
    echo $OUTPUT->notification($message, $type);
}

$mform->display();

echo $OUTPUT->footer();

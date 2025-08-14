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
 * TODO describe file filemanager
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

require_admin();

$url = new moodle_url('/local/devassist/filemanager.php', []);
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

$baseroot = $CFG->dirroot;
do {
    $baseroot = dirname($baseroot);
} while ($baseroot !== dirname($baseroot));

if (!isset($_GET['p'])) {
    $path = substr($CFG->dirroot, strlen($baseroot));
    $path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
    $url->param('p', $path);
    redirect($url);
}

$homeurl = (new moodle_url('/'))->out();
define('FM_EMBED', true);
define('FM_SELF_URL', $url->out(false));
define('FM_ROOT_URL', $homeurl);
define('FM_SHOW_HIDDEN', true);
define('FM_ROOT_PATH', $baseroot);
define('FM_SESSION_ID', session_name() . '_fm');

require __DIR__ . '/filemanager/tinyfilemanager.php';

echo '<hr>';
echo '<a href="' . $homeurl .'" class="btn btn-secondary">' . format_string($SITE->fullname) . '</a>';

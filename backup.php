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
 * Backup page.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

admin_externalpage_setup('local_devassist_backups');
require_admin();

$confirm = optional_param('confirm', false, PARAM_BOOL);
$download = optional_param('download', false, PARAM_BOOL);
$thing = optional_param('thing', '', PARAM_ALPHAEXT);

if ($download && confirm_sesskey()) {
    $class = "local_devassist\local\backup\backup_{$thing}";
    $class::download(true);
}

$form = new local_devassist\form\backup(null, null, 'post', '_blank');

if ($data = $form->get_data()) {
    $backup = local_devassist\local\backup\backup_base::get_instance($data->type);
    $backup->process();
    exit;
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();

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

use local_devassist\local\restore\restore_base;
/**
 * TODO describe file restore
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_OUTPUT_BUFFERING', true);

require_once('../../config.php');
require_once("{$CFG->libdir}/adminlib.php");

admin_externalpage_setup('local_devassist_restore');
require_admin();

$form = new local_devassist\form\restore();
if ($data = $form->get_data()) {
    $restorer = restore_base::get_instance($data->type);
    $restorer->set_upload_form($form);
    $restorer->process();
    exit;
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();

<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     local_devassist
 * @category    admin
 * @copyright   2024 MohammadFarouk <phun.for.physics@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $langsorter = new admin_externalpage('local_devassest_lang_sorter',
                                            get_string('lang_sorter', 'local_devassist'),
                                        new moodle_url('/local/devassist/lang_sorter.php'));
    $ADMIN->add('development', $langsorter);
    $pluginsbackup = new admin_externalpage('local_devassist_plugins_backup',
                                            get_string('plugins_backup', 'local_devassist'),
                                            new moodle_url('/local/devassist/plugins_backup.php'));
    $ADMIN->add('development', $pluginsbackup);
}

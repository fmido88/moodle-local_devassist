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

namespace local_devassist\form;
use core_plugin_manager;
use local_devassist\common;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir."/formslib.php");
/**
 * Class lang_sorter form
 *
 * @package    local_devassist
 * @copyright  2024 MohammadFarouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lang_sorter extends \moodleform {
    /**
     * Lang-sorter form definition.
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'lang_sorter', get_string('lang_sorter', 'local_devassist'));

        $mform->addElement('html', common::get_backup_warning());

        common::add_plugins_selection_options($mform);

        $mform->addElement('checkbox', 'spaces', get_string('letters_spaces', 'local_devassist'));
        $mform->setDefault('spaces', 1);

        $this->add_action_buttons(false);
    }
}

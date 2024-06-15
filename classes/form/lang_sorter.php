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
use core_component;

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

        $pluginman = core_plugin_manager::instance();
        $types = $pluginman->get_plugin_types();

        $components = [];
        foreach ($types as $component => $path) {
            $components[$component] = $pluginman->plugintype_name($component);
        }

        $mform->addElement('select', 'type', get_string('plugin_type', 'local_devassist'), $components);
        foreach ($components as $type => $name) {
            $plugins = $pluginman->get_plugins_of_type($type);
            $options = [];
            foreach ($plugins as $base) {
                $options[$base->name] = $base->displayname;
            }

            $mform->addElement('select', $type, $name, $options);
            $mform->hideIf($type, 'type', 'neq', $type);
        }

        $this->add_action_buttons();
    }
}

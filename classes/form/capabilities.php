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

use local_devassist\common;
use tool_certificate\plugin_manager;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . "/formslib.php");

/**
 * Class capabilities
 *
 * @package    local_devassist
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capabilities extends \moodleform {
    /**
     * The component to edit
     * @var string
     */
    protected $component;
    /**
     * The context levels options
     * @var array
     */
    protected $contextlevelsoptions;
    /**
     * All other capabilities
     * @var array
     */
    protected $othercapabilitiesoptions;
    /**
     * Site roles options
     * @var array
     */
    protected $rolesoptions;
    /**
     * Risks options
     * @var array
     */
    protected $riskoptions;
    /**
     * The permissions options.
     * @var array
     */
    protected $permissions;
    /**
     * Form definition.
     */
    protected function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        $pluginselected = $this->optional_param('plugins', false, PARAM_BOOL);
        $type = $this->optional_param('type', '', PARAM_ALPHANUMEXT);
        if (!$pluginselected || empty($type)) {
            return $this->plugin_selector();
        }

        $plugin = $this->optional_param($type, '', PARAM_ALPHANUMEXT);
        $this->component = $type . "_" . $plugin;

        $header = $OUTPUT->heading(get_string('cap_edit_component', 'local_devassist', $this->component), 3);
        $mform->addElement('html', $header);

        $pluginman = \core_plugin_manager::instance();
        $plugininfo = $pluginman->get_plugin_info($this->component);
        $mform->addElement('html', common::get_backup_warning($plugininfo->rootdir . '/db/access.php'));

        $capabilities = $this->get_exited_capabilities();

        $i = $this->optional_param('i', count($capabilities) + 1, PARAM_INT);
        $addmore = optional_param('addmore', false, PARAM_BOOL);
        if ($addmore) {
            $i++;
        }

        $j = 0;
        foreach ($capabilities as $capname => $default) {
            $j++;
            $default['capname'] = $capname;
            $this->add_cap_edit_fragment($j, $default);
        }

        for ($z = $j + 1; $z <= $i; $z++) {
            $this->add_cap_edit_fragment($z);
        }

        $mform->addElement('hidden', 'i');
        $mform->setType('i', PARAM_INT);
        $mform->setDefault('i', $i);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUMEXT);
        $mform->setDefault('type', $type);

        $mform->addElement('hidden', $type);
        $mform->setType($type, PARAM_ALPHANUMEXT);
        $mform->setDefault($type, $plugin);

        $mform->addElement('hidden', 'plugins');
        $mform->setType('plugins', PARAM_BOOL);
        $mform->setDefault('plugins', true);

        $mform->addElement('submit', 'addmore', get_string('addmore', 'local_devassist'));

        $this->add_action_buttons();
    }

    /**
     * Add elements to select the plugin.
     */
    protected function plugin_selector() {
        global $OUTPUT;
        $mform = $this->_form;

        $mform->addElement('html', common::get_backup_warning());

        $deprecatewarning = get_string('capability_deprecation_warn', 'local_devassist');
        $warn = $OUTPUT->notification($deprecatewarning, 'warning', false);
        $mform->addElement('html', $warn);

        common::add_plugins_selection_options($mform);

        $mform->addElement('submit', 'plugins', get_string('submit'));
    }

    /**
     * Load the capabilities existed in the plugin already if any.
     * @return array
     */
    protected function get_exited_capabilities() {
        $capabilities = [];

        $pluginman = \core_plugin_manager::instance();
        $info = $pluginman->get_plugin_info($this->component);

        $filepath = $info->rootdir . '/db/access.php';
        if (file_exists($filepath)) {
            include_once($filepath);
        }

        return $capabilities;
    }

    /**
     * Add a capability edit form fragment.
     * @param int $i increment identifier
     * @param array $default the default values
     */
    protected function add_cap_edit_fragment($i, $default = []) {
        $mform = $this->_form;
        $default = (array)$default;

        $mform->addElement('header', 'capno' . $i, get_string('capno', 'local_devassist', $i));

        $mform->addElement('text', 'capname' . $i, get_string('capname', 'local_devassist'));
        $mform->setType('capname' . $i, PARAM_TEXT);
        if (!empty($default['capname'])) {
            list($type, $plugin) = explode('_', $this->component, 2);
            $default['capname'] = str_ireplace($type . '/' . $plugin . ':', '', $default['capname']);
            $mform->setDefault('capname' . $i, $default['capname']);
        }

        $risk = $mform->addElement('select', 'riskbitunmask' . $i,
                                    get_string('riskbitunmask', 'local_devassist'),
                                    $this->get_risks_options());
        $risk->setMultiple(true);
        if (isset($default['riskbitmask'])) {
            $mform->setDefault('riskbitunmask' . $i, array_keys($this->unmask_risks($default)));
        }

        $typeoptions = [
            'read' => get_string('read', 'local_devassist'),
            'write' => get_string('write', 'local_devassist'),
        ];
        $mform->addElement('select', 'captype' . $i, get_string('captype', 'local_devassist'), $typeoptions);
        if (!empty($default['captype'])) {
            $mform->setDefault('captype' . $i, $default['captype']);
        }

        $mform->addElement('select', 'contextlevel' . $i,
                            get_string('contextlevel', 'local_devassist'),
                            $this->get_contextlevel_option());
        if (!empty($default['contextlevel'])) {
            $mform->setDefault('contextlevel' . $i, $default['contextlevel']);
        }

        $roles = $this->get_roles_options();
        foreach ($roles as $short => $name) {
            $radioname = 'role' . $i . '_' . $short;
            $radioarray = [];
            foreach ($this->get_permissions_options() as $per => $pname) {
                $radioarray[] = $mform->createElement('radio', $radioname, '', $pname, $per);
            }

            $mform->addGroup($radioarray, 'radioar', $name, ' ', false);

            if (isset($default['archetypes'][$short])) {
                $mform->setDefault($radioname, $default['archetypes'][$short]);
            } else {
                $mform->setDefault($radioname, CAP_INHERIT);
            }
        }

        $options = [
            'multiple' => false,
            'noselectionstring' => '',
        ];
        $mform->addElement('autocomplete', 'clonepermissionsfrom' . $i,
                            get_string('clonepermissionsfrom', 'local_devassist'),
                            $this->get_capability_options(), $options);
        if (isset($default['clonepermissionsfrom'])) {
            $mform->setDefault('clonepermissionsfrom' . $i, $default['clonepermissionsfrom']);
        }
    }

    /**
     * Get the risks options.
     * @return array
     */
    protected function get_risks_options() {
        if (isset($this->riskoptions)) {
            return $this->riskoptions;
        }
        $this->riskoptions = [];
        $risks = get_all_risks();
        foreach ($risks as $name => $risk) {
            $this->riskoptions[(int)$risk] = get_string($name.'short', 'admin');
        }
        return $this->riskoptions;
    }

    /**
     * Get roles option
     * @return array
     */
    protected function get_roles_options() {
        global $DB;
        if (isset($this->rolesoptions)) {
            return $this->rolesoptions;
        }
        $this->rolesoptions = [];
        $roles = role_fix_names(get_all_roles(), null, ROLENAME_ORIGINAL);
        foreach ($roles as $role) {
            $this->rolesoptions[$role->shortname] = format_string($role->localname);
        }
        return $this->rolesoptions;
    }

    /**
     * Get the context levels options
     * @return array
     */
    protected function get_contextlevel_option() {
        if (isset($this->contextlevelsoptions)) {
            return $this->contextlevelsoptions;
        }
        $this->contextlevelsoptions = [];
        $levels = \context_helper::get_all_levels();
        foreach ($levels as $level => $classname) {
            $this->contextlevelsoptions[$level] = \context_helper::get_level_name($level);
        }
        return $this->contextlevelsoptions;
    }

    /**
     * Get existent capability options
     * @return array
     */
    protected function get_capability_options() {
        if (isset($this->othercapabilitiesoptions)) {
            return $this->othercapabilitiesoptions;
        }
        $this->othercapabilitiesoptions = ['' => ''];
        $caps = get_all_capabilities();
        foreach ($caps as $cap) {
            $this->othercapabilitiesoptions[$cap['name']] = $cap['name'];
        }
        return $this->othercapabilitiesoptions;
    }

    /**
     * Get permissions options with names
     * @return array
     */
    protected function get_permissions_options() {
        if (isset($this->permissionsoptions)) {
            return $this->permissionsoptions;
        }
        $this->permissions = [];
        $allpermissions = [
            CAP_INHERIT  => 'notset',
            CAP_ALLOW    => 'allow',
            CAP_PREVENT  => 'prevent' ,
            CAP_PROHIBIT => 'prohibit',
        ];

        $this->strperms = [];
        foreach ($allpermissions as $value => $permname) {
            $this->permissions[$value] = get_string($permname, 'core_role');
        }
        return $this->permissions;
    }
    /**
     * Unmask bit risks.
     * @param array $capability
     * @return array
     */
    protected function unmask_risks($capability) {
        $masked = (int)($capability['riskbitmask'] ?? 0);
        $allrisks = get_all_risks();
        $risks = [];
        foreach ($allrisks as $name => $risk) {
            if ($masked & (int)$risk) {
                $risks[$risk] = $name;
            }
        }
        return $risks;
    }
}

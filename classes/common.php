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

namespace local_devassist;
use core_plugin_manager;

/**
 * Class common
 *
 * @package    local_devassist
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class common {
    /**
     * Get the standard moodle boilerplate
     * @return string
     */
    public static function get_standard_moodle_boilerplate() {
        "\n";
        return ""
        . "// This file is part of Moodle - http://moodle.org/" . "\n"
        . "//"."\n"
        . "// Moodle is free software: you can redistribute it and/or modify"."\n"
        . "// it under the terms of the GNU General Public License as published by"."\n"
        . "// the Free Software Foundation, either version 3 of the License, or"."\n"
        . "// (at your option) any later version."."\n"
        . "//"."\n"
        . "// Moodle is distributed in the hope that it will be useful,"."\n"
        . "// but WITHOUT ANY WARRANTY; without even the implied warranty of"."\n"
        . "// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the" . "\n"
        . "// GNU General Public License for more details." . "\n"
        . "//" . "\n"
        . "// You should have received a copy of the GNU General Public License" . "\n"
        . "// along with Moodle.  If not, see <http://www.gnu.org/licenses/>." . "\n" . "\n";
    }
    /**
     * Get moodle internal check to be written in top of a php file.
     * @return string
     */
    public static function get_moodle_internal_check() {
        return "defined('MOODLE_INTERNAL') || die();" . "\n" . "\n";
    }
    /**
     * Get the file doc block.
     * @param string $package
     * @param string $copyright
     * @param string $license
     * @param string $description
     */
    public static function get_file_doc_block($package = '', $copyright = '', $license = '', $description = '') {
        $return = "/**" . "\n";
        if (!empty($description)) {
            $return .= $description;
        } else {
            $return .= " * Add description here";
        }
        $return .= " *" . "\n";

        if (!empty($package)) {
            $return .= " * @package    $package" . "\n";
        }
        if (!empty($copyright)) {
            $return .= " * @copyright  $copyright" . "\n";
        }
        if (!empty($license)) {
            $return .= " * @license    $license" . "\n";
        }
        $return .= " */" . "\n";
        return $return;
    }
    /**
     * Get the default file doc for certain component.
     * @param string $component
     * @return string
     */
    public static function get_default_file_doc_block($component) {
        global $USER;
        $copyright = get_config('local_devassist', 'def_copyright');
        if (empty($copyright)) {
            $name = fullname($USER);
            $email = $USER->email;
            $copywrite = "$name <$email>";
        }

        $year = getdate()['year'];
        $copyright = $year . ", " . $copyright;

        $license = get_config('local_devassist', 'def_license');
        if (empty($license)) {
            $license = 'http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later';
        }

        return self::get_file_doc_block($component, $copywrite, $license);
    }

    /**
     * Get backup warning to be print before any editing form.
     * @param string $file
     */
    public static function get_backup_warning($file = '') {
        global $OUTPUT;
        if (!empty($file)) {
            $msg = get_string('backup_file_warning', 'local_devassist', $file);
        } else {
            $msg = get_string('backup_general_warning', 'local_devassist');
        }
        return $OUTPUT->notification($msg, 'warning', false);
    }

    /**
     * Check if the developer tools options enabled or not before
     * using certain pages.
     * @param bool $redirect
     * @return bool
     */
    public static function check_developer_tools_enabled($redirect = true) {
        $enabled = (bool)get_config('local_devassist', 'devtoolsenabled');

        if (!$enabled && $redirect) {
            $url = new \moodle_url('/admin/settings.php', ['section' => 'local_devassist_settings']);
            $msg = get_string('enabledevtools_error', 'local_devassist');
            redirect($url, $msg, null, \core\notification::ERROR);
        }

        return $enabled;
    }
    /**
     * Add plugin selection elements to a form.
     *
     * @param \MoodleQuickForm $mform
     * @param string|null $type
     * @return void
     */
    public static function add_plugins_selection_options(&$mform, $type = null) {
        $pluginman = core_plugin_manager::instance();
        $types = $pluginman->get_plugin_types();

        $components = [];
        $options = [];
        foreach ($types as $component => $path) {
            if ($type && $component !== $type) {
                continue;
            }

            $components[$component] = $pluginman->plugintype_name($component) . " ($component)";
            $plugins = $pluginman->get_plugins_of_type($component);

            $options[$component] = [];
            foreach ($plugins as $base) {
                if ($base->is_standard()) {
                    // We don't miss with standard plugins.
                    continue;
                }
                $options[$component][$base->name] = $base->displayname . " ({$component}_{$base->name})";
            }

            if (empty($options[$component])) {
                unset($options[$component]);
                unset($components[$component]);
                continue;
            }
        }

        asort($components);
        $mform->addElement('select', 'type', get_string('plugin_type', 'local_devassist'), $components);

        foreach ($components as $type => $name) {
            $mform->addElement('select', $type, $name, $options[$type]);
            $mform->hideIf($type, 'type', 'neq', $type);
        }
    }
}

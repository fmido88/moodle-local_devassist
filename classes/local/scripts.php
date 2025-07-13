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

namespace local_devassist\local;

/**
 * Some scripts that maybe usefull to some developers.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class scripts {
    /**
     * When adding a new custom profile field, some auth plugins add new configuration for this
     * field (map, updateloacal, updateremote and lock).
     * The problem is:
     *
     * 1- when deleting a profile field this configuration record still there and if the admin
     * add a new field with the same shortname the old config will be used.
     *
     * 2- When the custom field edited by changing the accent of letters (upper or lower cases)
     * the problem happen that each time reaching /admin/upgradesettings.php it been recognized as new
     * settings and wouldn't be saved properly even when reaching these settings from the auth settings page
     * the same problem exists.
     *
     * This script fixes the two problem by delete all config records that belongs to deleted profile fields.
     * And rename the name field in the config to match the accent of existence once.
     *
     * For now expert users can call this method from evaluating code page but the right thing to do is to run this
     * after profile field get updated or deleted in a hook callback or event observer.
     * @return void
     */
    public static function fix_auth_profile_fields_configs() {
        global $DB;
        $prefixes = [
            'field_map_profile_field_',
            'field_updatelocal_profile_field_',
            'field_updateremote_profile_field_',
            'field_lock_profile_field_',
        ];
        $fields     = $DB->get_records('user_info_field', null, '', 'id, shortname');
        $shortnames = [];

        foreach ($fields as $field) {
            $shortnames[] = $field->shortname;
        }

        $allconfigs = [];

        foreach ($shortnames as $shortname) {
            foreach ($prefixes as $prefix) {
                $allconfigs[] = $prefix . $shortname;
            }
        }

        $allconfiglower = array_map('strtolower', $allconfigs);

        $params      = [];
        $namelikesql = '(';
        $namelikes   = [];
        $i           = 1;

        foreach ($prefixes as $prefix) {
            $namelikes[]         = $DB->sql_like('cfg.name', ':name' . $i, false, false);
            $params['name' . $i] = $prefix . '%';
            $i++;
        }
        $namelikesql .= implode(' OR ', $namelikes) . ')';
        $authlike       = $DB->sql_like('cfg.plugin', ':auth', false, false);
        $params['auth'] = 'auth_%';
        $sql            = "SELECT *
                FROM {config_plugins} cfg
                WHERE $namelikesql
                AND $authlike";
        $configs = $DB->get_records_sql($sql, $params);

        $problematic = [];
        $notexist    = [];

        foreach ($configs as $config) {
            if (!in_array(strtolower($config->name), $allconfiglower, true)) {
                $notexist[] = $config;
            } else if (!in_array($config->name, $allconfigs)) {
                foreach ($allconfigs as $name) {
                    if (strtolower($name) === strtolower($config->name)) {
                        $config->oldname = $config->name;
                        $config->name    = $name;
                        $problematic[]   = $config;
                        break;
                    }
                }
            }
        }

        foreach ($notexist as $todelete) {
            unset($todelete->value);
            $DB->delete_records('config_plugins', (array)$todelete);
        }

        foreach ($problematic as $update) {
            $DB->update_record('config_plugins', $update);
        }
    }

    /**
     * Get plugins with invalid version.php files.
     * @return array{path: mixed, reason: string[]}
     */
    public static function get_invalid_plugins() {
        $invalidplugins = [];

        $plugintypes = \core_component::get_plugin_types();

        foreach ($plugintypes as $type => $typedir) {
            $plugs = \core_component::get_plugin_list($type);

            foreach ($plugs as $plug => $fullplug) {
                $module          = new \stdClass();
                $plugin          = new \stdClass();
                $plugin->version = null;

                $versionfile = $fullplug . DIRECTORY_SEPARATOR . 'version.php';
                $component   = "{$type}_{$plug}";

                if (!file_exists($versionfile)) {
                    $invalidplugins[] = [
                        'reason' => 'File version.php not exists.',
                        'path'   => $fullplug,
                    ];
                    continue;
                }

                include($versionfile);

                // Check if the legacy $module syntax is still used.
                if (!is_object($module) || (count((array)$module) > 0)) {
                    $invalidplugins[] = [
                        'reason' => "Unsupported \$module syntax detected in version.php of the $component plugin.",
                        'path'   => $fullplug,
                    ];
                    continue;
                }

                // Check if the component is properly declared.
                if (empty($plugin->component) || ($plugin->component !== $component)) {
                    $invalidplugins[] = [
                        'reason' => "Plugin $component does not declare valid \$plugin->component in its version.php.",
                        'path'   => $fullplug,
                    ];
                }
            }
        }

        return $invalidplugins;
    }

    /**
     * Delete plugins with invalid version.php files.
     * @return void
     */
    public static function delete_invalid_plugins() {
        $list = self::get_invalid_plugins();

        foreach ($list as $item) {
            @remove_dir($item['path']);
        }
    }
}

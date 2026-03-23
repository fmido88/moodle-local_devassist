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

namespace local_devassist\local\backup;

use core\exception\coding_exception;
use core\exception\moodle_exception;
use core_plugin_manager;

/**
 * Class backup_plugins
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_plugins extends backup_base {
    /**
     * Excluded list of plugins.
     * @var string[]
     */
    protected array $excluded = [];

    #[\Override]
    public function backup() {
        global $CFG;
        require_once($CFG->dirroot . '/local/devassist/locallib.php');
        $pluginman = core_plugin_manager::instance();

        // phpcs:ignore moodle.Commenting.InlineComment.DocBlock
        /**
         * @var \core\plugininfo\base[][]
         */
        $plugininfo = $pluginman->get_plugins();

        $this->trace("Listing all plugins files");
        $count = 0;
        $tempdir = self::get_temp_dir();
        foreach ($plugininfo as $type => $plugins) {
            $this->trace("Start adding plugins of type: $type", 1);
            $typecopied = 0;
            foreach ($plugins as $name => $plugin) {
                if ($plugin->get_status() === core_plugin_manager::PLUGIN_STATUS_MISSING) {
                    $this->trace("The path of the component {$plugin->component} not found...", 3);
                    continue;
                }

                if ($plugin->is_standard()) {
                    continue;
                }

                if (in_array($plugin->component, $this->excluded, true)) {
                    $this->trace("The component {$plugin->component} is excluded from backup", 3);
                    continue;
                }

                $this->trace($count++ . ": Adding: {$plugin->displayname} ({$plugin->component})", 2);
                $subdir = str_replace($CFG->dirroot, '', $plugin->rootdir);

                $dir = $tempdir . $subdir;
                $this->add_files_recursive($plugin->rootdir, $dir);
                $typecopied++;
            }
            $this->trace("$typecopied Plugins has been added of type:  $type", 1);
        }

        $this->trace("$count plugins has been added.");
    }

    public function exclude_plugin(string $component) {
        if (clean_param($component, PARAM_COMPONENT) !== $component) {
            throw new coding_exception("Invalid component $component");
        }
        $this->excluded = array_unique(array_merge($this->excluded, [$component]));
    }
    /**
     * Recursively add files of a given plugin to the list of files to be added to the zip archive.
     *
     * @param  string $source Source path
     * @param  string $dest   Destination path
     * @return bool
     */
    protected function add_files_recursive($source, $dest) {
        // Simple copy for a file.
        if (is_file($source)) {
            $archivepath = self::get_archive_path($dest);
            $this->tozipfiles[$archivepath] = $source;
            return true;
        }

        if ((!\defined('LOCAL_DEVASSIST_DEVENV') || !LOCAL_DEVASSIST_DEVENV)
            && (str_ends_with($source, '.git') || str_ends_with($source, 'node_modules'))) {
            return true;
        }

        // Loop through the folder.
        $dir = dir($source);

        while (false !== ($entry = $dir->read())) {
            // Skip pointers.
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories.
            if ($dest !== "$source/$entry") {
                $this->add_files_recursive("$source/$entry", "$dest/$entry");
            }
        }

        // Clean up.
        $dir->close();

        return true;
    }
}

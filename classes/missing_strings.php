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
use core_string_manager;

/**
 * Class missing_strings
 *
 * @package    local_devassist
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class missing_strings {
    /**
     * The plugin type
     * @var string
     */
    protected string $type;
    /**
     * The plugin name
     * @var string
     */
    protected string $plugin;
    /**
     * The full component name in frankstyle
     * @var string
     */
    protected string $component;
    /**
     * The language
     * @var string
     */
    protected string $lang;
    /**
     * @var core_string_manager
     */
    protected core_string_manager $strman;
    /**
     * @var string
     */
    protected $rootpath;
    /**
     * @var \core\plugininfo\base
     */
    protected $info;
    /**
     * Array of missing strings.
     * @var array
     */
    protected array $strings = [];
    /**
     * Array of special strings in db files or privacy.
     * @var array
     */
    protected array $special;
    /**
     * Array of existed strings in the component
     * @var array
     */
    protected array $exist = [];
    /**
     * Directories to be excluded
     * @var array
     */
    protected array $exclude = [];
    /**
     * Construct a utility class to search for missing strings in a specific component.
     * @param string $component
     * @param string $lang the language code (default English "en")
     */
    public function __construct($component, $lang = 'en') {

        $this->component = clean_param($component, PARAM_COMPONENT);
        list($this->type, $this->plugin) = explode('_', $component, 2);

        $pluginman = core_plugin_manager::instance();
        $this->info = $pluginman->get_plugin_info($component);
        $this->rootpath = $this->info->rootdir;

        $this->special = [
            'access'   => $this->rootpath . "/db/access.php",
            'caches'   => $this->rootpath . "/db/caches.php",
            'messages' => $this->rootpath . "/db/messages.php",
            'privacy'  => $this->rootpath . "/classes/privacy/provider.php",
        ];

        $langfile = $this->rootpath . "/lang/$lang/$component.php";

        $string = [];
        if (file_exists($langfile)) {
            include_once($langfile);
        }

        $this->exist = $string;
        unset($string);

        // Exclude any third party libraries files.
        if (file_exists($this->rootpath . "/thirdpartylibs.xml")) {
            $thirdparty = simplexml_load_file($this->rootpath . "/thirdpartylibs.xml");
            // I need to read the tags location from each tag library.
            foreach ($thirdparty->xpath('/libraries/library/location') as $location) {
                $this->exclude[] = $this->rootpath . "/$location";
            }
        }
    }

    /**
     * Destruct the class and unset every thing.
     */
    public function close() {
        foreach ($this as $property) {
            unset($property);
        }
    }

    /**
     * Start the work and search for missing strings.
     * @return array of missing strings
     */
    public function get_missing_strings() {
        $this->check_mandatory_strings();
        $this->scan_files($this->rootpath);
        return array_unique($this->strings);
    }
    /**
     * Check for some mandatory strings for certain types
     * of plugins
     * @return void
     */
    protected function check_mandatory_strings() {
        $this->check_string_exist('pluginname');
        if ($this->type == 'paygw') {
            $this->check_string_exist('gatewayname');
            $this->check_string_exist('gatewaydescription');
        }
    }
    /**
     * Exclude third party libraries from scan.
     * @param string $dir directory to check
     * @return bool
     */
    protected function exclude($dir) {
        return in_array($dir, $this->exclude);
    }

    /**
     * Check if the string not exist and add it to missing strings array.
     * @param string $key the key of the string
     * @return void
     */
    protected function check_string_exist($key) {
        if (!array_key_exists($key, $this->exist)) {
            $this->strings[] = $key . '///' . $this->component;
        }
    }

    /**
     * Start scanning all files in a given directory.
     * @param string $source file or directory path
     * @return void
     */
    protected function scan_files($source) {
        global $CFG;
        if (empty($strings)) {
            $strings = [];
        }
        // Simple copy for a file.
        if (is_file($source)) {
            // Make sure this is an php file.
            if (strpos($source, '.php') === (strlen($source) - 4)) {
                switch ($source) {
                    case $this->special['access']:
                        return $this->look_for_access_strings($source);
                    case $this->special['caches']:
                        return $this->look_for_cache_def($source);
                    case $this->special['messages']:
                        return $this->look_for_msg_providers($source);
                    case $this->special['privacy']:
                        return $this->look_for_privacy_providers($source);
                    default:
                        return $this->look_for_get_string($source);
                }
            } else if (strpos($source, '.mustache') == (strlen($source) - 9)) {

                return $this->scan_mustache($source);

            } else {
                return;
            }
        }

        // Loop through the folder.
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers and third party libraries.
            if ($entry == '.' || $entry == '..' || $this->exclude($entry)) {
                continue;
            }

            $this->scan_files("$source/$entry");
        }

        // Clean up.
        $dir->close();
        return;
    }

    /**
     * Scan a mustache file for strings.
     * @param string $source
     * @return void
     */
    protected function scan_mustache($source) {
        $content = file_get_contents($source);

        // Remove comments and white spaces.
        $content = preg_replace('/{{!--.*?--}}/s', '', $content);
        $content = preg_replace('/\s+/', '', $content);

        preg_match_all('/{{#str}}(.*?){{\/str}}/', $content, $matches);

        foreach ($matches[1] as $string) {
            $parts = explode(',', $string);

            if (count($parts) < 2) {
                continue;
            }

            $identifier = clean_param(trim($parts[0]), PARAM_STRINGID);
            $component = clean_param(trim($parts[1]), PARAM_COMPONENT);

            if ($component == $this->component) {
                $this->check_string_exist($identifier);
            }
        }
    }

    /**
     * Look for required strings by privacy provider and see of any are missing.
     * @param string $file the file path /classes/privacy/provider.php
     * @return void
     */
    protected function look_for_privacy_providers($file) {
        require_once($file);
        $class = $this->component . "\privacy\provider";
        if (!class_exists($class)) {
            return;
        }

        if (method_exists($class, 'get_reason')) {
            $this->check_string_exist($class::get_reason());
        } else if (method_exists($class, 'get_metadata')) {
            $collection = new \core_privacy\local\metadata\collection($this->component);
            $collection = $class::get_metadata($collection);
            $collections = $collection->get_collection();
            foreach ($collections as $type) {
                $this->check_string_exist($type->get_summary());
                $fields = $type->get_privacy_fields();
                foreach ($fields as $field) {
                    if (!empty($field)) {
                        $this->check_string_exist($field);
                    }
                }
            }
        }
    }

    /**
     * Check for missing strings required by message providers
     * @param string $file the path to /db/message.php
     * @return void
     */
    protected function look_for_msg_providers($file) {
        $messageproviders = [];
        include($file);
        $messageproviders = array_keys($messageproviders);
        foreach ($messageproviders as $msg) {
            $key = "messageprovider:" . $msg;
            $this->check_string_exist($key);
        }
    }

    /**
     * Look for missing strings required by the capabilities.
     * @param string $file path to /db/access.php
     * @return void
     */
    protected function look_for_access_strings($file) {
        $capabilities = [];
        include($file);
        $capabilities = array_keys($capabilities);
        foreach ($capabilities as $cap) {
            $key = str_replace($this->type . '/', '', $cap);
            $this->check_string_exist($key);
        }
    }

    /**
     * Look for missing strings required to describe cache def.
     * @param string $file path to /db/caches.php
     */
    protected function look_for_cache_def($file) {
        $definitions = [];
        include($file);
        $definitions = array_keys($definitions);
        foreach ($definitions as $def) {
            $key = 'cachedef_' . $def;
            $this->check_string_exist($key);
        }
    }

    /**
     * Search for each call of function get_string() in a php file
     * @param string $file
     * @return void
     */
    protected function look_for_get_string($file) {
        $filecontents = file_get_contents($file);

        // Regular expression to match get_string() and lang_string() calls.
        $pattern = '/(get_string|new\s+lang_string)\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*/';

        // Search for every function call and extract $identifier and $component.
        preg_match_all($pattern, $filecontents, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            // Don't look for other component or dynamic keys.
            if ($match[3] !== $this->component || strstr($match[2], '$')) {
                continue;
            }

            $this->check_string_exist($match[2]);
        }
        $this->look_for_help_button($filecontents);
    }

    /**
     * For moodle forms, check for addHelpButton
     * ->addHelpButton($ignore, $identifier, $component)
     * @param string $filecontents
     * @return void
     */
    protected function look_for_help_button($filecontents) {
        // Regular expression to match ->addHelpButton() calls.
        $pattern = '/->addHelpButton\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/';

        preg_match_all($pattern, $filecontents, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            // Don't look for other components or dynamic keys.
            if ($match[3] !== $this->component || strstr($match[2], '$')) {
                continue;
            }

            $this->check_string_exist($match[2] . "_help");
            $this->check_string_exist($match[2]);
        }
    }
}

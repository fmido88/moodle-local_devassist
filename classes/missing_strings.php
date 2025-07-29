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
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
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
        list($this->type, $this->plugin) = explode('_', $this->component, 2);

        $pluginman = core_plugin_manager::instance();
        $this->info = $pluginman->get_plugin_info($this->component);
        if ($this->info === null) {
            debugging("The component $component is not existed...");
            return;
        }

        $this->rootpath = $this->info->rootdir;

        $this->special = [
            'access'   => $this->rootpath . "/db/access.php",
            'caches'   => $this->rootpath . "/db/caches.php",
            'messages' => $this->rootpath . "/db/messages.php",
            'privacy'  => $this->rootpath . "/classes/privacy/provider.php",
        ];

        $langfile = $this->rootpath . "/lang/$lang/{$this->component}.php";

        $string = [];
        if (file_exists($langfile)) {
            include($langfile);
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
        } else if ($this->type == 'mod') {
            $this->check_string_exist('modulename');
            $this->check_string_exist('modulename_help');
            $this->check_string_exist('modulenameplural');
            $this->check_string_exist('modulename_link');
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
     * @param ?string $component
     * @return void
     */
    protected function check_string_exist($key, $component = null) {
        if ($component === null) {
            $component = $this->component;
        }

        $exist = false;
        if ($component === $this->component) {
            $exist = array_key_exists($key, $this->exist);
        } else {
            if (!isset($this->strman)) {
                $this->strman = get_string_manager(true);
            }
            $exist = $this->strman->string_exists($key, $component);
        }

        if (!$exist) {
            $this->strings[] = $key . '///' . $component;
        }
    }

    /**
     * Start scanning all files in a given directory.
     * @param string $source file or directory path
     * @return void
     */
    protected function scan_files($source) {

        // Simple scan for a file.
        if (is_file($source)) {
            $ext = pathinfo($source)['extension'] ?? '';
            // Make sure this is an php file.
            switch ($ext) {
                case 'php':
                    return match($source) {
                        $this->special['access']   => $this->look_for_access_strings($source),
                        $this->special['caches']   => $this->look_for_cache_def($source),
                        $this->special['messages'] => $this->look_for_msg_providers($source),
                        $this->special['privacy']  => $this->look_for_privacy_providers($source),
                        default => $this->look_for_get_string($source),
                    };
                case 'mustache':
                    return $this->scan_mustache($source);
                case 'js':
                    return $this->look_for_get_string_js($source);
                default:
                    return;
            }
        }

        // Loop through the folder.
        $dir = dir($source);
        while (false !== ($entry = $dir->read())) {
            // Skip pointers and third party libraries.
            if ($entry == '.' || $entry == '..' || $this->exclude("$source/$entry")) {
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

            $parts[1] ??= 'moodle';

            $identifier = clean_param(trim($parts[0]), PARAM_STRINGID);
            $component = clean_param(trim($parts[1]), PARAM_COMPONENT);

            if ($component == $this->component) {
                $this->check_string_exist($identifier, $component);
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
        $pattern = '/(get_string|new\s+lang_string|->string_for_js)\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*([^)\s]+))?/';

        // Search for every function call and extract $identifier and $component.
        preg_match_all($pattern, $filecontents, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $key = $match[2];
            $component = $match[3] ?? null;

            if (str_contains($key, '$')) {
                continue;
            }

            // Clean trailing characters from component (like closing parenthesis or comma).
            if ($component !== null) {
                $component = rtrim($component, ")\t\n\r\0\x0B,");
            }

            // Skip if component is dynamic (not a quoted string literal).
            if ($component !== null && !preg_match('/^[\'"]([^\'"]+)[\'"]$/', $component)) {
                continue;
            }

            // Strip quotes if present.
            $component = $component ? trim($component, "'\"") : 'moodle';

            $this->check_string_exist($key, $component);
        }

        $this->look_for_help_button($filecontents);
    }

    /**
     * Search for missing strings inside js files.
     * @param string $file
     * @return void
     */
    protected function look_for_get_string_js($file) {
        $filecontents = file_get_contents($file);

        // 1. Match direct calls like getString('key', 'component').
        $pattern = '/\b(get_string|getString|prefetchString)\s*\(\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*[\'"]([^\'"]+)[\'"])?/';
        preg_match_all($pattern, $filecontents, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $this->check_string_exist($match[2], $match[3] ?? 'core');
        }

        // 2. Match object-style calls like getStrings([{key: 'yes', component: 'core'}]).
        $pattern = '/\b(getStrings|get_strings)\s*\(\s*\[(.*?)\]\s*\)/s';
        preg_match_all($pattern, $filecontents, $arraymatches, PREG_SET_ORDER);

        foreach ($arraymatches as $request) {
            $arraycontent = $request[2];

            // Now extract { key: '...', component: '...' } objects from array content.
            $pattern = '/\{\s*key\s*:\s*[\'"]([^\'"]+)[\'"](?:\s*,\s*component\s*:\s*[\'"]([^\'"]+)[\'"])?\s*\}/';
            preg_match_all($pattern, $arraycontent, $objectmatches, PREG_SET_ORDER);

            foreach ($objectmatches as $match) {
                $this->check_string_exist($match[1], $match[2] ?? 'core');
            }
        }
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
            if (strstr($match[2], '$')) {
                continue;
            }

            $match[3] ??= 'moodle';
            $this->check_string_exist($match[2] . "_help", $match[3]);
            $this->check_string_exist($match[2], $match[3]);
        }
    }
}

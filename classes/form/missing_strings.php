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
use local_devassist\missing_strings as util;
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
class missing_strings extends \moodleform {
    /**
     * The lang to be checked.
     * @var string
     */
    protected string $lang;
    /**
     * The component
     * @var string
     */
    protected string $fullcomponent;

    /**
     * Lang-sorter form definition.
     */
    protected function definition() {

        $data = (object)$this->_customdata;

        if ($type = $data->type ?? null) {
            $component = $data->$type ?? null;
        }

        $fullcomponent = $data->fullcomponent;

        if (empty($fullcomponent) && !empty($type) && !empty($component)) {
            $fullcomponent = $type . "_" . $component;
        }

        if (empty($fullcomponent) || $this->is_cancelled()) {
            $this->select_plugin_definition();
            return;
        }

        $this->fullcomponent = $fullcomponent;
        $this->lang = $data->lang;

        $util = new util($fullcomponent, $this->lang);
        $strings = $util->get_missing_strings();

        if (empty($strings)) {
            $this->select_plugin_definition();
            \core\notification::success(get_string('no_missing_strings', 'local_devassist', $fullcomponent));
            return;
        }

        $this->add_strings_definition($strings);
    }

    /**
     * Page 1 - selection of plugins
     */
    protected function select_plugin_definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'missing_strings', get_string('search_missing_strings', 'local_devassist'));

        $warn = common::get_backup_warning();
        $mform->addElement('html', $warn);

        common::add_plugins_selection_options($mform);

        $strman = get_string_manager();
        $languages = $strman->get_list_of_languages();

        $lang = $mform->addElement('select', 'lang', get_string('language'), $languages);
        $lang->setSelected($this->lang ?? 'en');
        $mform->addHelpButton('lang', 'language', 'local_devassist');

        $mform->addElement('submit', 'search_strings', get_string('search'));
    }

    /**
     * Page 2 - adding missing strings.
     * @param array $strings the missing strings to be added
     */
    protected function add_strings_definition($strings) {
        $mform = $this->_form;

        $language = get_string_manager()->get_list_of_languages()[$this->lang];
        $title = get_string('missing_strings', 'local_devassist', $this->fullcomponent) . "\"$language\"";
        $mform->addElement('header', 'missing_strings', $title);

        $mform->addElement('html', common::get_backup_warning());

        $strings = array_unique($strings);
        foreach ($strings as $string) {
            list($identifier, $component) = explode('///', $string, 2);
            $noteng = $this->lang !== 'en';
            if ($component == $this->fullcomponent) {
                $mform->addElement('text', 'newstring__' . $identifier, $identifier, ['size' => 100]);
                $mform->setType('newstring__' . $identifier, PARAM_TEXT);
                if ($noteng) {
                    $mform->addElement('static', $identifier . "__en",
                                        $identifier . "__en",
                                        new \lang_string($identifier, $component, null, 'en'));
                }
            } else {
                $mform->addElement('static', $identifier.'__'.$component, $component, $identifier);
            }
        }

        $mform->addElement('hidden', 'fullcomponent');
        $mform->setType('fullcomponent', PARAM_TEXT);
        $mform->setDefault('fullcomponent', $component);

        $mform->addElement('hidden', 'lang');
        $mform->setType('lang', PARAM_TEXT);
        $mform->setDefault('lang', $this->lang);

        $this->add_action_buttons();
    }

    /**
     * Adding new strings to lang files
     * this function return false if the form not submitted yet
     * -1 in case of error writing the file
     * +ve integer (size of the file) in case of success
     * @return int|bool
     */
    public function add_string_file() {
        if ((!$data = $this->get_data())) {
            return false;
        }

        if (!isset($this->fullcomponent)) {
            return false;
        }

        if (empty($data->submitbutton)) {
            return false;
        }

        $fullcomponent = $this->fullcomponent;

        $pluginman = core_plugin_manager::instance();
        $info = $pluginman->get_plugin_info($fullcomponent);
        $lang = $this->lang;
        $filepath = "$info->rootdir/lang/$lang/$fullcomponent.php";
        check_dir_exists($info->rootdir . "/lang/" . $lang);

        if (file_exists($filepath)) {
            $content = file_get_contents($filepath);
        } else {
            $content = "<?php\n";
            $content .= common::get_standard_moodle_boilerplate();
            $content .= common::get_default_file_doc_block($fullcomponent);
            $content .= common::get_moodle_internal_check();
        }

        $content .= "\n";

        $i = 0;
        foreach ($data as $key => $value) {
            if (strpos($key, 'newstring__') !== 0 || empty($value)) {
                continue;
            }
            $i++;
            $identifier = str_replace('newstring__', '', $key);
            $content .= '$string[\''.$identifier.'\'] = \''.str_replace('\'', '\\\'', $value).'\';' . "\n";
        }

        $done = @file_put_contents("$info->rootdir/lang/$lang/$fullcomponent.php", $content);
        if ($done) {
            purge_caches(['lang' => true]);
            return $i;
        }

        return -1;
    }
}

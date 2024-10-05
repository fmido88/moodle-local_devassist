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

/**
 * Class capabilities_edit
 *
 * @package    local_devassist
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class capabilities_edit {
    /**
     * Array of risks to be replaced
     * @var array
     */
    protected $riskreplace = [
        RISK_CONFIG      => 'RISK_CONFIG',
        RISK_PERSONAL    => 'RISK_PERSONAL',
        RISK_SPAM        => 'RISK_SPAM',
        RISK_XSS         => 'RISK_XSS',
        RISK_DATALOSS    => 'RISK_DATALOSS',
        RISK_MANAGETRUST => 'RISK_MANAGETRUST',
    ];
    /**
     * Array of capabilities to be replaced
     * @var array
     */
    protected $capreplace = [
        CAP_ALLOW    => 'CAP_ALLOW',
        CAP_INHERIT  => 'CAP_INHERIT',
        CAP_PROHIBIT => 'CAP_PROHIBIT',
        CAP_PREVENT  => 'CAP_PREVENT',
    ];
    /**
     * Array of context levels to be replaced.
     * @var array
     */
    protected $contextreplacment = [
        CONTEXT_SYSTEM    => 'CONTEXT_SYSTEM',
        CONTEXT_COURSECAT => 'CONTEXT_COURSECAT',
        CONTEXT_COURSE    => 'CONTEXT_COURSE',
        CONTEXT_MODULE    => 'CONTEXT_MODULE',
        CONTEXT_BLOCK     => 'CONTEXT_BLOCK',
        CONTEXT_USER      => 'CONTEXT_USER',
    ];
    /**
     * New capabilities after parsing it to strings
     * @var array
     */
    protected $capabilities;
    /**
     * The component
     * @var string
     */
    protected $component;
    /**
     * access.php file path
     * @var string
     */
    protected $filepath;
    /**
     * Ready the data to be writing in access.php file
     * @param \stdClass $data the data submitted from the form.
     */
    public function __construct($data) {
        $this->component = $data->type . '_' . $data->{$data->type};
        $pman = \core_plugin_manager::instance();
        $info = $pman->get_plugin_info($this->component);

        check_dir_exists($info->rootdir . '/db');
        $this->filepath = $info->rootdir . '/db/access.php';

        $this->parse_data($data);
    }

    /**
     * Analyze the data submitter from the form
     * and parse the capabilities array as a strings to get it ready
     * to be written in the new file.
     * @param \stdClass $data
     */
    protected function parse_data($data) {
        $number = $data->i;
        $capabilities = [];
        $roles = get_all_roles();
        for ($i = 1; $i <= $number; $i++) {
            if (empty($data->{'capname' . $i})) {
                continue;
            }
            $capname = $data->type . '/' . $data->{$data->type} . ':';
            $capname .= $data->{'capname' . $i};
            $capability = [];
            if (!empty($data->{'riskbitunmask'.$i})) {
                $capability['riskbitmask'] = $this->mask_risks_as_string($data->{'riskbitunmask'.$i});
            }
            $capability['captype'] = $data->{'captype'.$i};
            $capability['contextlevel'] = $this->contextreplacment[$data->{'contextlevel' . $i}];
            $capability['archetypes'] = [];
            foreach ($roles as $role) {
                $roleelement = 'role'.$i.'_'.$role->shortname;
                if (!empty($data->{$roleelement}) && $data->{$roleelement} != CAP_INHERIT) {
                    $capability['archetypes'][$role->shortname] = $this->capreplace[$data->{$roleelement}];
                }
            }

            if (!empty($data->{'clonepermissionsfrom' . $i})) {
                $capability['clonepermissionsfrom'] = $data->{'clonepermissionsfrom' . $i};
            }

            $capabilities[$capname] = $capability;
        }
        $this->capabilities = $capabilities;
    }

    /**
     * Bit mask the risks again.
     * @param array $risks
     * @return string
     */
    protected function mask_risks_as_string($risks) {
        $strings = [];
        foreach ($risks as $key => $code) {
            $strings[] = $this->riskreplace[$code];
        }
        return implode(' | ', $strings);
    }

    /**
     * Get a string (code snippet) of the capabilities array
     * to be ready to be written in php
     *
     * @return string
     */
    public function capabilities_as_string() {
        $output = '$capabilities = [' . "\n";
        foreach ($this->capabilities as $name => $capability) {
            $output .= '    \'' . $name . '\' => [' . "\n";
            foreach ($capability as $key => $value) {
                $output .= '        \'' . $key . '\' => ';
                if (is_array($value)) {
                    $output .= "[" . "\n";
                    foreach ($value as $v => $cap) {
                        $output .= "            '$v' => $cap," . "\n";
                    }
                    $output .= "        ]," . "\n";
                } else if (in_array($key, ['riskbitmask', 'contextlevel'])) {
                    $output .= $value . "," . "\n";
                } else {
                    $output .= '\'' . $value . "'," . "\n";
                }
            }
            $output .= '    ],' . "\n";
        }
        $output .= '];' . "\n";
        return $output;
    }

    /**
     * Get the full content suppose to be written in the new file.
     * @return string
     */
    public function get_new_file_content() {
        $content = $this->get_file_header();
        $content .= $this->capabilities_as_string();
        return $content;
    }
    /**
     * Re-write the new access.php file.
     * @return void
     */
    public function rewrite_new_file() {
        global $PAGE;
        $content = $this->get_new_file_content();
        $success = file_put_contents($this->filepath, $content);
        if ($success) {
            $msg = get_string('file_edit_success', 'local_devassist');
            $type = 'success';
        } else {
            $msg = get_string('file_edit_error', 'local_devassist');
            $type = 'error';
        }
        redirect($PAGE->url, $msg, null, $type);
    }
    /**
     * Get the header of the file from the old file or
     * the defaults if not exists.
     * @return string
     */
    protected function get_file_header() {
        if (file_exists($this->filepath)) {
            $content = file_get_contents($this->filepath);

            $position = strpos($content, '$capabilities');
            return trim(substr($content, 0, $position)) . "\n" . "\n";
        }
        $header = "<?php" . "\n";
        $header .= common::get_standard_moodle_boilerplate();
        $header .= common::get_default_file_doc_block($this->component);
        $header .= common::get_moodle_internal_check();
        return $header;
    }

}

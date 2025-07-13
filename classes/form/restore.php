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

use local_devassist\local\restore\restore_base;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Restoring form by uploading a zip file.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore extends \moodleform {
    /**
     * Form definition.
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $mform->addElement('header', 'general', get_string('restore', 'local_devassist'));
        $mform->addHelpButton('general', 'restore', 'local_devassist');

        $number = $this->optional_param('number', 0, PARAM_INT);

        if (!$number) {
            $mform->addElement('text', 'number', get_string('numberofchunks', 'local_devassist'));
            $mform->setDefault('number', 1);
            $mform->addRule('number', null, 'required');
        } else {
            $mform->addElement('hidden', 'number', $number);
        }
        $mform->setType('number', PARAM_INT);

        for ($i = 0; $i < $number; $i++) {
            $elementname = 'zipfile' . $i;

            $serial = '_' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $mform->addElement(
                'filepicker',
                $elementname,
                get_string('uploadzipfile', 'local_devassist', $serial),
                null,
                ['accepted_types' => '.zip', 'maxbytes' => 0]
            );
            $mform->addHelpButton($elementname, 'uploadzipfile', 'local_devassist');
            $mform->addRule($elementname, null, 'required', null, 'client');
        }

        if ($number) {
            $options = restore_base::get_options();
            $mform->addElement('select', 'type', get_string('restore_type', 'local_devassist'), $options);
            $mform->addHelpButton('type', 'restore_type', 'local_devassist');

            $mform->addElement(
                'static',
                'restore_plugins',
                $options['plugins'],
                get_string('restore_plugins_help', 'local_devassist')
            );
            $mform->hideIf('restore_plugins', 'type', 'neq', 'plugins');

            $mform->addElement(
                'static',
                'restore_database_tables',
                $options['database_tables'],
                get_string('restore_database_tables_help', 'local_devassist')
            );
            $mform->hideIf('restore_database_tables', 'type', 'neq', 'database_tables');

            $mform->addElement(
                'static',
                'restore_files',
                $options['files'],
                get_string('restore_files_help', 'local_devassist')
            );
            $mform->hideIf('restore_files', 'type', 'neq', 'files');

            $mform->addElement('hidden', 'uploaded');
            $mform->setConstant('uploaded', true);
        } else {
            $mform->addElement('hidden', 'uploaded', false);
        }
        $mform->setType('uploaded', PARAM_BOOL);

        backup::add_maintenance_note($mform);

        if ($number) {
            $this->add_action_buttons(true, get_string('restorefromzipfile', 'local_devassist'));
        } else {
            $this->add_action_buttons(false, get_string('next'));
        }
    }
}

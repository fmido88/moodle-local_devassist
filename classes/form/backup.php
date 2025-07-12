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

use local_devassist\local\backup\backup_base;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');

/**
 * Backup form to select what to backup.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup extends \moodleform {
    /**
     * Form definition.
     * @return void
     */
    protected function definition() {
        global $OUTPUT;
        $mform = $this->_form;

        $options = backup_base::get_options();
        $mform->addElement('select', 'type', get_string('backup_type', 'local_devassist'), $options);
        $mform->addHelpButton('type', 'backup_type', 'local_devassist');

        $confirm = $OUTPUT->box(get_string('backup_confirm', 'local_devassist'), 'box py-3 modal-body');
        $mform->addElement('html', $confirm);

        self::add_maintenance_note($mform);

        $mform->addElement('submit', 'confirm', get_string('backup'));
    }

    /**
     * Display a warning that the site will enter the maintenance mode.
     * @param  \MoodleQuickForm $mform
     * @return void
     */
    public static function add_maintenance_note(\MoodleQuickForm $mform) {
        global $OUTPUT;
        $msg  = get_string('maintenance_warning', 'local_devassist');
        $html = $OUTPUT->notification($msg, \core\notification::WARNING);
        $mform->addElement('html', $html);
    }
}

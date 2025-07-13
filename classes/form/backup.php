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
use local_devassist\local\backup\backup_database_tables;

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
        global $OUTPUT, $DB;
        $mform = $this->_form;

        $options = backup_base::get_options();
        $mform->addElement('select', 'type', get_string('backup_type', 'local_devassist'), $options);
        $mform->addHelpButton('type', 'backup_type', 'local_devassist');

        $mform->addElement('text', 'chunksize', get_string('chunksize', 'local_devassist'));
        $mform->setType('chunksize', PARAM_ALPHANUM);
        $mform->addHelpButton('chunksize', 'chunksize', 'local_devassist');

        $defaultchunk = backup_base::get_max_upload_size(false);
        $mform->setDefault('chunksize', $defaultchunk);

        $mform->addElement('header', 'advanced', get_string('advancebackup', 'local_devassist'));
        $mform->setAdvanced('advanced');

        $tables = $DB->get_tables();
        $options = array_combine($tables, $tables);

        $select = $mform->addElement('select', 'ignoredtables', get_string('ignoredtables', 'local_devassist'), $options);
        $select->setMultiple(true);
        $mform->addHelpButton('ignoredtables', 'ignoredtables', 'local_devassist');
        $mform->hideIf('ignoredtables', 'type', 'neq', 'database_tables');
        $backer = new backup_database_tables(false, false);
        $mform->setDefault('ignoredtables', $backer->get_ignored_tables());
        $mform->setAdvanced('ignoredtables');

        $mform->addElement('textarea', 'ignoredfileareas', get_string('ignoredfileareas', 'local_devassist'));
        $mform->setType('ignoredfileareas', PARAM_TEXT);
        $mform->addHelpButton('ignoredfileareas', 'ignoredfileareas', 'local_devassist');
        $mform->hideIf('ignoredfileareas', 'type', 'eq', 'plugins');
        $mform->setDefault('ignoredfileareas', $backer->get_excluded_fileareas(true));
        $mform->setAdvanced('ignoredfileareas');

        $mform->addElement('static', 'instruction',
                            get_string('instruction', 'local_devassist'),
                            get_string('backup_instruction', 'local_devassist'));
        $mform->closeHeaderBefore('instruction');

        $listlink = self::print_backup_list_link(true);
        $mform->addElement('html', $OUTPUT->heading($listlink, 5));

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

    /**
     * Print a link to the backup files list page.
     * @param bool $return
     * @return string|void
     */
    public static function print_backup_list_link($return = false) {
        $label = get_string('backupfileslist', 'local_devassist');
        $url = new \moodle_url('/local/devassist/backuplist.php');
        $link = \html_writer::link($url, $label);
        if ($return) {
            return $link;
        }
        echo $link;
    }
}

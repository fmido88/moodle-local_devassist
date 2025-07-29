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

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/course/reset_form.php");
/**
 * Class reset_courses
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reset_courses extends \course_reset_form {
    /**
     * Form definition.
     * @throws \moodle_exception
     * @return void
     */
    public function definition() {
        global $COURSE, $DB, $CFG;
        $mform = $this->_form;

        $courses = get_courses('all', 'c.category ASC, c.sortorder ASC', 'c.id, c.fullname, c.category');
        $options = [];

        foreach ($courses as $course) {
            if ($course->id == SITEID) {
                continue;
            }

            $coursecontext = \context_course::instance($course->id);
            if (!has_capability('moodle/course:reset', $coursecontext)) {
                continue;
            }

            $category = \core_course_category::get($course->category);
            $coursename = format_string($course->fullname, true, ['context' => $coursecontext]);
            $options[$course->id] = $category->get_nested_name(false) . ': ' . $coursename;
        }

        if (empty($options)) {
            throw new \moodle_exception('nopermissiontoaccesspage');
        }

        $select = $mform->addElement('select', 'courses', 'Courses', $options);
        $select->setMultiple(true);
        $mform->addRule('courses', get_string('required'), 'required', null, 'client');

        parent::definition();

        $mform->removeElement('id', true);
        $mform->removeElement('unenrol_users');
        $mform->removeElement('buttonar');
        $mform->removeElement('submitbutton');

        $roles = array_values(get_roles_for_contextlevels(CONTEXT_COURSE));
        $roles = array_combine($roles, $roles);
        $roles = role_fix_names($roles, null, ROLENAME_ORIGINALANDSHORT, true);

        $roles[0] = get_string('noroles', 'role');
        $roles = array_reverse($roles, true);
        $attributes = [
            'multiple' => 1,
            'size' => min(count($roles), 10),
        ];
        $unenrolroles = $mform->createElement('select', 'unenrol_users',
                                            get_string('unenrolroleusers', 'enrol'), $roles, $attributes);
        $mform->insertElementBefore($unenrolroles, 'rolesdelete');

        $unsupportedmods = [];
        if ($allmods = $DB->get_records('modules') ) {
            foreach ($allmods as $mod) {
                $modname = $mod->name;
                $modfile = $CFG->dirroot."/mod/$modname/lib.php";
                $modresetcourseformdefinition = $modname.'_reset_course_form_definition';
                $modresetuserdata = $modname.'_reset_userdata';
                if (file_exists($modfile)) {
                    if ($DB->count_records($modname, ['course' => $COURSE->id])) {
                        continue; // Already added in parent definition.
                    }
                    if (!$DB->count_records($modname)) {
                        continue; // Not has instances in any course.
                    }
                    include_once($modfile);
                    if (function_exists($modresetcourseformdefinition)) {
                        $modresetcourseformdefinition($mform);
                    } else if (!function_exists($modresetuserdata)) {
                        $unsupportedmods[] = $mod;
                    }
                } else {
                    debugging('Missing lib.php in '.$modname.' module');
                }
            }
        }

        // Mention unsupported mods.
        if (!empty($unsupportedmods)) {
            $mform->addElement('header', 'unsupportedheader', get_string('resetnotimplemented'));
            $mform->addElement('static', 'unsupportedinfo', get_string('resetnotimplementedinfo'));
            foreach ($unsupportedmods as $mod) {
                $mform->addElement('static', 'unsup'.$mod->name, get_string('modulenameplural', $mod->name));
            }
        }

        $elements = $mform->_elements;
        foreach ($elements as $element) {
            if (is_a($element, 'HTML_QuickForm_header')) {
                $mform->setExpanded($element->getName());
            }
        }

        $this->add_action_buttons(true, get_string('resetcourses', 'local_devassist'));
    }

    /**
     * Validation
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = [];
        $enddateerrors = [];

        foreach ($data['courses'] as $id) {
            $data['id'] = $id;
            $errors = parent::validation($data, $files);
            if (!empty($errors['reset_end_date'])) {
                $course = get_course($id);
                $enddateerrors[] = $errors['reset_end_date'] . " " . format_string($course->fullname);
            }
        }
        if (!empty($enddateerrors)) {
            $errors['reset_end_date'] = implode('<br>', $enddateerrors);
        }
        return $errors;
    }
}

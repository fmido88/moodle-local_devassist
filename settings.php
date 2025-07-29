<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     local_devassist
 * @category    admin
 * @copyright   2024 MohammadFarouk <phun.for.physics@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Adding new admin category.
    $ADMIN->add('development', new admin_category(
        'local_devassist_pages',
        get_string('devtools', 'local_devassist'),
        false
    ));

    // Bulk courses reset.
    $reset = new admin_externalpage(
        'local_devassist_resetcourses',
        get_string('bulkresetcourses', 'local_devassist'),
        new moodle_url('/local/devassist/resetcourses'),
    );
    $ADMIN->add('local_devassist_pages', $reset);

    // Backups.
    $backups = new admin_externalpage(
        'local_devassist_backups',
        get_string('backups', 'local_devassist'),
        new moodle_url('/local/devassist/backup.php')
    );
    $ADMIN->add('local_devassist_pages', $backups);

    // Restore.
    $restore = new admin_externalpage(
        'local_devassist_restore',
        get_string('restore', 'local_devassist'),
        new moodle_url('/local/devassist/restore.php')
    );
    $ADMIN->add('local_devassist_pages', $restore);

    if (local_devassist\common::check_developer_tools_enabled(false)) {
        // Lang string sorter.
        $langsorter = new admin_externalpage(
            'local_devassest_lang_sorter',
            get_string('lang_sorter', 'local_devassist'),
            new moodle_url('/local/devassist/lang_sorter.php')
        );
        $ADMIN->add('local_devassist_pages', $langsorter);

        // Search for missing lang string and local translations.
        $missinglang = new admin_externalpage(
            'local_devassist_missing_lang',
            get_string('missing_lang_strings', 'local_devassist'),
            new moodle_url('/local/devassist/missing_strings.php')
        );
        $ADMIN->add('local_devassist_pages', $missinglang);

        // Edit capabilities in access.php files.
        $capedit = new admin_externalpage(
            'local_devassist_cap_edit',
            get_string('cap_edit_page', 'local_devassist'),
            new moodle_url('/local/devassist/cap_edit.php')
        );
        $ADMIN->add('local_devassist_pages', $capedit);

        // Evaluate PHP code.
        $testcode = new admin_externalpage(
            'local_devassist_test_code',
            get_string('evaluatephpcode', 'local_devassist'),
            new moodle_url('/local/devassist/testcode.php')
        );
        $ADMIN->add('local_devassist_pages', $testcode);

        // Edit plugins server files.
        $editpluginserverfiles = new admin_externalpage(
            'local_devassist_edit_plugin_server_files',
            get_string('editpluginserverfiles', 'local_devassist'),
            new moodle_url('/local/devassist/editpluginserverfiles.php')
        );
        $ADMIN->add('local_devassist_pages', $editpluginserverfiles);
    }

    // Adding general settings.
    $pluginname = get_string('pluginname', 'local_devassist');
    $settings   = new admin_settingpage('local_devassist_settings', $pluginname);

    $settings->add(new admin_setting_configtext(
        'local_devassist/def_copyright',
        get_string('def_copyright', 'local_devassist'),
        get_string('def_copyright_desc', 'local_devassist'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_devassist/def_license',
        get_string('def_license', 'local_devassist'),
        get_string('def_license_desc', 'local_devassist'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_devassist/devtoolsenabled',
        get_string('enabledevtools', 'local_devassist'),
        get_string('enabledevtools_desc', 'local_devassist'),
        0
    ));

    $ADMIN->add('localplugins', $settings);
}

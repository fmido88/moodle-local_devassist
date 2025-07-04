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
 * Plugin strings are defined here.
 *
 * @package     local_devassist
 * @category    string
 * @copyright   2024 MohammadFarouk <phun.for.physics@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
$string['addmore'] = 'Add more';
$string['backup_file_warning'] = 'WARNING! This action will change the content of the file {$a} Make sure you have a backup from it';
$string['backup_general_warning'] = 'WARNING! Please back up the plugin files before submitting the form as these actions may change some of its files content';
$string['cannot_write_file'] = 'Error while try to write the lang file';
$string['cap_edit'] = 'Edit capabilities on access.php file';
$string['cap_edit_component'] = 'Edit capabilities for component ({$a})';
$string['cap_edit_page'] = 'Add/Edit capability in access.php files';
$string['capability_deprecation_warn'] = 'WARNING! This tool not edit or deprecate capabilities, and if the original file has a deprecated capability it may misbehave and you must re-edit it manually.';
$string['capname'] = 'Capability name';
$string['capno'] = 'Capability {$a}';
$string['captype'] = 'Capability type';
$string['clonepermissionsfrom'] = 'Clone permission from:';
$string['contextlevel'] = 'Context Level';
$string['def_copyright'] = 'Default copyright for new files';
$string['def_copyright_desc'] = 'If you left it empty the name of the editing user and email will be put by default.';
$string['def_license'] = 'Default license for new file';
$string['def_license_desc'] = 'leaving empty will use GNU GPL v3 by default';
$string['developerconfirmation'] = 'Are you a developer?';
$string['developerconfirmationbutton'] = 'Yes, I\'m a developer and I know what I\'m doing.';
$string['developerconfirmationtext'] = 'Make sure you know what are you doing before you use this page. This plugin is meant to be used on developing environment not a production site and by a developer.
This page could change the plugin file content, so make sure that you have a backup of these files before doing anything. ';
$string['developerwarning'] = 'This page could change the plugin file content or database, so make sure that you have a backup of these files before doing anything.';
$string['devtools'] = 'Developer Assistant Tools';
$string['editpluginserverfiles'] = 'Edit plugin php files';
$string['evaluatephpcode'] = 'Evaluate a php code';
$string['file_edit_error'] = 'Error while try to edit the file';
$string['file_edit_success'] = 'File edited successfully';
$string['fileupdated'] = 'The file {$a->file} has been updated successfully.
A backup file generated in {$a->backup}';
$string['lang_sorter'] = 'Language strings sorter';
$string['language'] = 'Language';
$string['language_help'] = 'Select the language required to search the lang strings, WARNING you should search for the English language first';
$string['letters_spaces'] = 'Add line spaces between different letters';
$string['missing_lang_strings'] = 'Search for missing language string and make a local translation';
$string['missing_strings'] = 'Missing strings for component ({$a})';
$string['no_missing_strings'] = 'No missing strings for component ({$a})';
$string['plugin_backup_confirm'] = 'Your are about to create backup of all additional plugins and download it as a zip file, this will take some time and downloaded automatically after it.';
$string['plugin_type'] = 'Plugin type';
$string['pluginname'] = 'Developer  Assist';
$string['plugins_backup'] = 'Plugins backup';
$string['privacy:metadata'] = 'The Developer assist plugin does not store any personal data.';
$string['read'] = 'Capability Read';
$string['riskbitunmask'] = 'Risks';
$string['search_missing_strings'] = 'Select component to look for missing strings';
$string['string_files_nothing'] = 'No lang files found for component {$a} or there is no permission to read or write these files';
$string['string_files_success'] = 'Language files successfully sorted:';
$string['strings_added_success'] = '{$a} strings added successfully.';
$string['write'] = 'Capability Write';

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
$string['advancebackup'] = 'Advanced backup options';


$string['backup_confirm'] = 'Your are about to create backup for the selected type of backup and download it as a zip file, this will take some time and get downloaded automatically after it.';
$string['backup_database_tables'] = 'Backup database records';
$string['backup_file_warning'] = 'WARNING! This action will change the content of the file {$a} Make sure you have a backup from it';
$string['backup_files'] = 'Backup files';
$string['backup_general_warning'] = 'WARNING! Please backup the plugin files before submitting the form as these actions may change some of its files content';
$string['backup_instruction'] = 'If you use this backup and restore for migration, you should backup and restore all things but for upgrading you only need backing up plugins and restore it manually before upgrade script run.
<br>In order to restore these data after migration you should do it in order:
<br>1- install new moodle in the new location.
<br>2- restore plugins
<br>3- install these plugins by going to \admin\index.php
<br>4- restore files
<br>5- restore database records
<br><br>This tool is still considered to be experimental so use it with cation.';
$string['backup_plugins'] = 'Backup plugins';
$string['backup_type'] = 'What you want to backup?';
$string['backup_type_help'] = 'Select what you want to backup:
Plugins: backup all additional plugins in a single zip file.
Database tables: All database tables except log store.
Files: all moodle data saved files in the system except draft and assignment feedbacks.';
$string['backupfileslist'] = 'Backup files list';
$string['backups'] = 'Backup plugins, files or database records.';
$string['backuptime'] = 'Backup time';
$string['bulkresetcourses'] = 'Bulk reset courses';


$string['cannot_write_file'] = 'Error while try to write the lang file';
$string['cap_edit'] = 'Edit capabilities on access.php file';
$string['cap_edit_component'] = 'Edit capabilities for component ({$a})';
$string['cap_edit_page'] = 'Add/Edit capability in access.php files';
$string['capability_deprecation_warn'] = 'WARNING! This tool not edit or deprecate capabilities, and if the original file has a deprecated capability it may misbehave and you must re-edit it manually.';
$string['capname'] = 'Capability name';
$string['capno'] = 'Capability {$a}';
$string['captype'] = 'Capability type';
$string['chunksize'] = 'Chunk size';
$string['chunksize_help'] = 'The max size of the output chunks (zip files) as if the backed up data exceeds this size it will divided into several files.';
$string['clonepermissionsfrom'] = 'Clone permission from:';
$string['contextlevel'] = 'Context Level';


$string['def_copyright'] = 'Default copyright for new files';
$string['def_copyright_desc'] = 'If you left it empty the name of the editing user and email will be put by default.';
$string['def_license'] = 'Default license for new file';
$string['def_license_desc'] = 'leaving empty will use GNU GPL v3 by default';
$string['delete'] = 'Delete';
$string['developerconfirmation'] = 'Are you a developer?';
$string['developerconfirmationbutton'] = 'Yes, I\'m a developer and I know what I\'m doing.';
$string['developerconfirmationtext'] = 'Make sure you know what are you doing before you use this page. This plugin is meant to be used on developing environment not a production site and by a developer.
This page could change the plugin file content, so make sure that you have a backup of these files before doing anything. ';
$string['developerwarning'] = 'This page could change the plugin file content or database, so make sure that you have a backup of these files before doing anything.';
$string['devtools'] = 'Developer Assistant Tools';
$string['download'] = 'Download';
$string['downloadfile'] = 'Download {$a}';


$string['editpluginserverfiles'] = 'Edit plugin php files';
$string['enabledevtools'] = 'Enable developer tools';
$string['enabledevtools_desc'] = 'Enable the access to development pages, this must be enabled only if you are a developer and know the risks.<br />
The developer tools available:
<ul>
<li>Language string sorter</li>
<li>Search for missing language strings</li>
<li>Edit access file</li>
<li>Test a php code</li>
<li>Edit plugins files</li>
</ul>';
$string['enabledevtools_error'] = 'Access to this page is denied untill developer tools option is enables in the plugin setting.';
$string['evaluatephpcode'] = 'Evaluate a php code';


$string['file_edit_error'] = 'Error while try to edit the file';
$string['file_edit_success'] = 'File edited successfully';
$string['filename'] = 'File name';
$string['filesize'] = 'File size';
$string['fileupdated'] = 'The file {$a->file} has been updated successfully.
A backup file generated in {$a->backup}';


$string['ignoredfileareas'] = 'Ignored file areas';
$string['ignoredfileareas_help'] = 'Write down the list of fileareas to be ignored (This should match that in both backups of db tables and files) each in separate line
<br>each line contain:
<br>component: required
<br>filearea: optional
<br>separated by ","
<br><br>example:
<br>user, icon
<br>course, overviewfiles
<br>local_myplugin, description
<br>mod_page';
$string['ignoredtables'] = 'Ignored database tables';
$string['ignoredtables_help'] = 'Database tables to be ignored in the backup process.';
$string['instruction'] = 'Instructions';


$string['lang_sorter'] = 'Language strings sorter';
$string['language'] = 'Language';
$string['language_help'] = 'Select the language required to search the lang strings, WARNING you should search for the English language first';
$string['letters_spaces'] = 'Add line spaces between different letters';


$string['maintenance_warning'] = 'When the process started the site will enter the maintenance mode and will return after completion, the process may take a long time. Make sure to not interrupt the process by closing the window or refreshing it.';
$string['missing_lang_strings'] = 'Search for missing language string and make a local translation';
$string['missing_strings'] = 'Missing strings for component ({$a})';


$string['no_missing_strings'] = 'No missing strings for component ({$a})';
$string['nobackupfiles'] = 'No backed up files found';
$string['numberofchunks'] = 'Number of parts';


$string['othercomponentmissingstring'] = 'The string identifier \'{$a->identifier}\' in another component \'{$a->component}\' is missing and called from this component {$a->currentcomponent}.';


$string['plugin_backup_confirm'] = 'Your are about to create backup of all additional plugins and download it as a zip file, this will take some time and downloaded automatically after it.';
$string['plugin_type'] = 'Plugin type';
$string['pluginname'] = 'Developer  Assist';
$string['plugins_backup'] = 'Plugins backup';
$string['privacy:metadata'] = 'The Developer assist plugin does not store any personal data.';


$string['read'] = 'Capability Read';
$string['resetcourses'] = 'Reset courses';
$string['restore'] = 'Restore';
$string['restore_database_tables'] = 'Restore database records';
$string['restore_database_tables_help'] = 'In order to restore database records, the moodle installation should be new and has no records added already, if the table not exist it will not be restored as this tool restores records only.
Cation: This is still experimental tool and must be used with cation, any existence record will be overridden.';
$string['restore_files'] = 'Restore files';
$string['restore_files_help'] = 'Restoring all files from a single zip file, this will not work until the database table files is restored first.';
$string['restore_help'] = 'Restore files, plugins and database tables that is backed up by this plugin in another moodle installation.';
$string['restore_plugins'] = 'Restore plugins';
$string['restore_plugins_help'] = 'The plugins will be uploaded directly to the system files, if one of the plugins already exists it will be overridden which may case misbehave in some cases, so make sure that all plugins need to be restored is not installed in this installation.';
$string['restore_type'] = 'What to restore?';
$string['restore_type_help'] = 'Select what you want to restore and it should match ';
$string['restorefromzipfile'] = 'Restore from zip file';
$string['riskbitunmask'] = 'Risks';


$string['search_missing_strings'] = 'Select component to look for missing strings';
$string['string_files_nothing'] = 'No lang files found for component {$a} or there is no permission to read or write these files';
$string['string_files_success'] = 'Language files successfully sorted:';
$string['strings_added_success'] = '{$a} strings added successfully.';


$string['uploadzipfile'] = 'Upload zip file {$a}';
$string['uploadzipfile_help'] = 'The uploaded zip file should be created by the same plugin during the backup process.';


$string['write'] = 'Capability Write';

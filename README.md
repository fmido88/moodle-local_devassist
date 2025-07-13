# Developer  Assist #

Developer Assistance provides several tools helping developers

- Language string sorter:
    * Automatically sort lang-strings for selected plugin
- Backup:
    * Plugins: Create a backup of all additional plugins in your moodle installation and download it as a zip file.
    * Database: Create a backup of your Moodle database records and download it as a zip file.
    * Files: Create a backup of all files stored in moodle system and download it as a single zip file.
- Restore:
    * Restore plugins, database records and files that has been backed up by the same plugin in another moodle installation.
- Missing language strings and local translation:
    * Searching all php files for any missing lang strings and easily adding them to lang files.
    * Also this tool is excellent to create local translation files for other languages.
- Editing and Adding capability
    * Easily add or edit capabilities in your plugin access.php file.
- Advanced utilities for developer users
    * Execute a php code from browser interface.
    * Edit plugins files from browser interface.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/local/devassist

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2023 Your Name <you@example.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.

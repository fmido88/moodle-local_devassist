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

/**
 * Helper functions
 *
 * @package    local_devassist
 * @copyright  2024 MohammadFarouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Copy a file, or recursively copy a folder and its contents
 *
 * @param string $source Source path
 * @param string $dest Destination path
 * @param array $files List of files copied
 * @return bool
 */
function local_devassist_copyr($source, $dest, &$files) {
    global $CFG;
    // Simple copy for a file.
    if (is_file($source)) {
        $archivepath = str_replace($CFG->dataroot . '/plugins/', '', $dest);
        $files[$archivepath] = $dest;
        return copy($source, $dest);
    }

    // Make destination directory.
    if (!is_dir($dest)) {
        mkdir($dest, $CFG->directorypermissions, true);
    }

    // Loop through the folder.
    $dir = dir($source);
    while (false !== $entry = $dir->read()) {
        // Skip pointers.
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        // Deep copy directories.
        if ($dest !== "$source/$entry") {
            local_devassist_copyr("$source/$entry", "$dest/$entry", $files);
        }
    }

    // Clean up.
    $dir->close();
    return true;
}

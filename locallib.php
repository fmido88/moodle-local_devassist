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
 * Helper functions.
 *
 * @package    local_devassist
 * @copyright  2024 MohammadFarouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @param mixed $source
 * @param mixed $dest
 * @param mixed $files
 */

/**
 * Copy a file, or recursively copy a folder and its contents.
 *
 * @param  string $source Source path
 * @param  string $dest   Destination path
 * @param  array  $files  List of files copied
 * @return bool
 */
function local_devassist_copyr($source, $dest, &$files) {
    global $CFG;

    // Simple copy for a file.
    if (is_file($source)) {
        $archivepath         = str_replace(make_temp_directory('local_devassist'), '', $dest);
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

/**
 * List all files in a directory.
 * @param string $directory
 * @param array  $list
 */
function local_devassist_list_dir_files($directory, &$list = []) {
    global $CFG;

    // Add the file to the list.
    if (is_file($directory)) {
        $list[] = clean_param(str_replace($CFG->dirroot, '', $directory), PARAM_PATH);

        return $list;
    }

    // Loop through the folder.
    $dir = dir($directory);

    while (false !== $entry = $dir->read()) {
        // Skip pointers and hidden.
        if (strpos($entry, '.') === 0) {
            continue;
        }

        if ($entry == 'node_modules') {
            continue;
        }

        local_devassist_list_dir_files("$directory/$entry", $list);
    }

    // Clean up.
    $dir->close();

    return $list;
}

/**
 * Exception handler to handle a test code.
 *
 * @param  Throwable $ex
 * @return string
 */
function local_devassist_test_exception_handler(Throwable $ex): string {
    global $CFG, $DB, $OUTPUT;

    // Detect active db transactions, rollback and log as error.
    abort_all_db_transactions();

    $info = get_exception_info($ex);

    $output = '';

    if (is_early_init($info->backtrace)) {
        $output .= bootstrap_renderer::early_error(
            $info->message,
            $info->moreinfourl,
            $info->link,
            $info->backtrace,
            $info->debuginfo,
            $info->errorcode
        );
    } else {
        try {
            if ($DB) {
                // If you enable db debugging and exception is thrown, the print footer prints a lot of rubbish.
                $DB->set_debug(0);
            }

            $obbuffer = '';

            if (!$OUTPUT->has_started()) {
                // It is really bad if library code throws exception when output buffering is on,
                // because the buffered text would be printed before our start of page.
                // NOTE: this hack might be behave unexpectedly in case output buffering is enabled in PHP.ini ...
                error_reporting(0); // Disable notices from gzip compression, etc.

                while (ob_get_level() > 0) {
                    $buff = ob_get_clean();

                    if ($buff === false) {
                        break;
                    }
                    $obbuffer .= $buff;
                }
                error_reporting($CFG->debug);
            }

            $message = '<p class="errormessage">' . s($info->message) . '</p>' .
                    '<p class="errorcode"><a href="' . s($info->moreinfourl) . '">' .
                    get_string('moreinformation') . '</a></p>';

            if (empty($CFG->rolesactive)) {
                $message .= '<p class="errormessage">' . get_string('installproblem', 'error') . '</p>';
                // It is usually not possible to recover from errors triggered during installation,
                // you may need to create a new database or use a different database prefix for new installation.
            }
            $output .= $OUTPUT->box($message, 'errorbox alert alert-danger', null, ['data-rel' => 'fatalerror']);

            $labelsep = get_string('labelsep', 'langconfig');

            if (!empty($info->debuginfo)) {
                $debuginfo = s($info->debuginfo); // Removes all nasty JS.
                $debuginfo = str_replace("\n", '<br />', $debuginfo); // Keep newlines.
                $label     = get_string('debuginfo', 'debug') . $labelsep;
                $output .= $OUTPUT->notification("<strong>$label</strong> " . $debuginfo, 'notifytiny');
            }

            if (!empty($info->backtrace)) {
                $label = get_string('stacktrace', 'debug') . $labelsep;
                $output .= $OUTPUT->notification("<strong>$label</strong> " . format_backtrace($info->backtrace), 'notifytiny');
            }

            if ($obbuffer !== '') {
                $label = get_string('outputbuffer', 'debug') . $labelsep;
                $output .= $OUTPUT->notification("<strong>$label</strong> " . s($obbuffer), 'notifytiny');
            }

            // Padding to encourage IE to display our error page, rather than its own.
            $output .= str_repeat(' ', 512);
        } catch (Exception $e) {
            $outex = $e;
        } catch (Throwable $e) {
            // Engine errors in PHP7 throw exceptions of type Throwable (this "catch" will be ignored in PHP5).
            $outex = $e;
        }

        if (isset($outex)) {
            $output .= bootstrap_renderer::early_error_content(
                $info->message,
                $info->moreinfourl,
                $info->link,
                $info->backtrace,
                $info->debuginfo
            );
            $outinfo = get_exception_info($outex);
            $output .= bootstrap_renderer::early_error_content(
                $outinfo->message,
                $outinfo->moreinfourl,
                $outinfo->link,
                $outinfo->backtrace,
                $outinfo->debuginfo
            );
        }
    }

    return $output;
}

/**
 * Block certain actions until the developer confirms that they are aware of the risks.
 * This is useful to prevent accidental changes in production environments.
 * @return void
 */
function local_devassist_display_developer_confirmation() {
    global $OUTPUT, $SESSION, $PAGE;

    require_admin();

    if (!empty($SESSION->local_devassist_developerconfirmed)) {
        return;
    }

    if (optional_param('devconfirm', false, PARAM_BOOL) && confirm_sesskey()) {
        $SESSION->local_devassist_developerconfirmed = true;

        return;
    }

    $confirm = new single_button(
        new moodle_url($PAGE->url, ['devconfirm' => true, 'sesskey' => sesskey()]),
        get_string('developerconfirmationbutton', 'local_devassist'),
        'post',
        single_button::BUTTON_DANGER
    );
    $cancel = new single_button(new moodle_url('/'), get_string('cancel'), 'get', single_button::BUTTON_SUCCESS);
    echo $OUTPUT->header(),
    $OUTPUT->heading(get_string('developerconfirmation', 'local_devassist')),

    $OUTPUT->confirm(
        get_string('developerconfirmationtext', 'local_devassist'),
        $confirm,
        $cancel,
    ),

    $OUTPUT->footer();
    die();
}

/**
 * Print a warning message for developers users only.
 * This should be used in every page that mess with server files or database.
 * The whole plugin meant to be used in development (non-production) environments only.
 * @param  bool $print Whether to print the message or add it to notifications stack.
 * @return void
 */
function local_devassist_add_warning_message($print = false) {
    global $OUTPUT;

    if ($print) {
        echo $OUTPUT->notification(get_string('developerwarning', 'local_devassist'), 'warning', false);

        return;
    }
    core\notification::warning(get_string('developerwarning', 'local_devassist'));
}

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
 * A library of functions that is used to change the config.php file.
 *
 * @package    local_devassist
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Getting the content of the dynamic config file.
 * @return string
 */
function local_devassist_get_multidomain_configfile() {
    return <<<PHP
        // Config file for multiple domains created by local_devassist.
        \$configs = glob(__DIR__ . DIRECTORY_SEPARATOR . "config.*.php");

        foreach (\$configs as \$i => \$configfile) {
            \$domain = str_replace(__DIR__ . DIRECTORY_SEPARATOR, "", \$configfile);
            \$domain = str_replace("..", "/", \$domain);
            \$domain = str_replace("__", ":", \$domain);

            \$domain = substr(\$domain, strpos(\$domain, ".") + 1);
            \$domain = substr(\$domain, 0, strpos(\$domain, ".php"));

            unset(\$configs[\$i]);
            \$configs[\$domain] = \$configfile;
        }

        \$target = '';
        if (php_sapi_name() === 'cli') {
            // Use environment variable or fallback.
            \$target = getenv('MOODLE_DOMAIN'); // You can set this before running the CLI.
            if (!isset(\$argv)) {
                \$argv = \$_SERVER['argv'];
            }

            if (!\$target && count(\$argv) > 1) {
                // Optional: allow passing domain as CLI arg like --domain=mysite.local.
                for (\$i = 1; \$i < count(\$argv); \$i++) {
                    if (str_starts_with(\$argv[\$i], '--domain=')) {
                        \$target = substr(\$argv[\$i], 9);
                        unset(\$argv[\$i], \$_SERVER['argv'][\$i]);
                        \$_SERVER['argv'] = \$argv = array_values(\$_SERVER['argv']);
                        break;
                    }
                }
            }
        } else {
            // Normal web request
            if (isset(\$_SERVER['HTTP_HOST'], \$_SERVER['SCRIPT_NAME'], \$_SERVER['SCRIPT_FILENAME'])) {
                \$hostport = explode(":", \$_SERVER["HTTP_HOST"]);
                \$target .= reset(\$hostport);

                if (\$_SERVER["SERVER_PORT"] != 80 && \$_SERVER["SERVER_PORT"] != "443") {
                    \$target .= ":" . \$_SERVER["SERVER_PORT"];
                }

                \$target .= explode(substr(\$_SERVER["SCRIPT_FILENAME"], strlen(__DIR__)), \$_SERVER["SCRIPT_NAME"])[0];
            }
        }

        if (\$target && isset(\$configs[\$target])) {
            require_once(\$configs[\$target]);
        } else {
            require_once("config-backup.php"); // fallback.
        }

        PHP;
}

/**
 * Rename the existent config file to the current domain reference.
 * @return void
 */
function local_devassist_rename_config() {
    global $CFG;
    $currentdomain = str_replace(['https://', 'http://'], '', $CFG->wwwroot);
    $currentdomain = str_replace(['/', ':'], ['..', '__'], $currentdomain);
    $newfilename = "{$CFG->dirroot}/config.{$currentdomain}.php";
    if (!file_exists($newfilename)) {
        copy("{$CFG->dirroot}/config.php", "{$CFG->dirroot}/config.{$currentdomain}.php");
        rename("{$CFG->dirroot}/config.php", "{$CFG->dirroot}/config-backup.php");
    } else {
        local_devassist_remove_config();
    }
}

/**
 * Remove the current config file by renaming it to config-delete.php
 * @return void
 */
function local_devassist_remove_config() {
    global $CFG;
    rename("{$CFG->dirroot}/config.php", "{$CFG->dirroot}/config-delete.php");
}

/**
 * Put the dynamic config file content to its location.
 * @return void
 */
function local_devassist_put_dynamic_config() {
    global $CFG;
    $content = local_devassist_get_multidomain_configfile();
    file_put_contents("{$CFG->dirroot}/config.php", $content);
}

/**
 * Check if the file is an installation file or dynamic generated file.
 * @return bool
 */
function local_devassist_is_installation_config() {
    global $CFG;
    $configfile = "{$CFG->dirroot}/config.php";
    if (!file_exists($configfile)) {
        return false;
    }

    $content = file_get_contents($configfile);

    return substr_count($content, '$CFG') > 2;
}

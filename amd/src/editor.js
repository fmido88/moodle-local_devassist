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
 * TODO describe module editor
 *
 * @module     local_devassist/editor
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import CodeMirror from "local_devassist/codemirror";
import $ from "jquery";

export const init = function(mode = 'php') {
    var textarea = $('textarea[name="code"]');
    if (textarea[0]) {

        // eslint-disable-next-line no-console
        console.log(mode);
        switch (mode) {
            case 'php':
                mode = {name: "php", startOpen: true};
                break;
            case 'js':
                mode = 'javascript';
                break;
            case 'mustache':
                mode = 'php';
                break;
            default:
        }

        let phpCodeMirror = CodeMirror.fromTextArea(textarea[0], {
            lineNumbers: true,
            mode: mode,
            tabSize: 4,
            lineWrapping: true,
            indentWithTabs: false,
            spellcheck: true,
            theme: "vscode-dark",
        });
        phpCodeMirror.setSize('100%', '500px');
        phpCodeMirror.on('change', function(cm) {
            cm.save();
        });
    }
};
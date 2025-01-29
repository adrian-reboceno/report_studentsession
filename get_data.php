<?php
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
 * Version metadata for the report_studentsession plugin.
 *
 * @package   report_studentsession
 * @copyright 2024, Universindad Ciudadana de Nuevo leon {@link http://www.ucnl.edu.mx/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Adrian Francisco Lozada Reboceño <adrian.lozada@ucnl.edu.mx>
 */

require_once(__DIR__.'/../../config.php');
defined('MOODLE_INTERNAL') || die();
use report_ucnl\helper;
require_login();
$context = context_system::instance();
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$flag = optional_param('flag', 0, PARAM_TEXT);
$role = optional_param('role', 0, PARAM_TEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);

switch ($flag) {
    case 'category':
        if ($categoryid == 0) {
            echo "";
            die();
        }
        $data = helper::get_subcategory($categoryid);
        foreach ($data as $key => $value) {
            echo "<option value='$key'>$value</option>";
        }
        break;
    case 'course':
        if ($categoryid == 0) {
            echo "";
            die();
        }
        $data = helper::get_course($categoryid);
        foreach ($data as $key => $value) {
            echo "<option value='$key'>$value</option>";
        }
        break;
    case 'group':
        
        break;
    case 'user':        
        if ($courseid == 0) {
            echo "";
            die();
        }       
        $data = helper::get_user($courseid, $role);
        foreach ($data as $key => $value) {
            echo "<option value='$key'>$value</option>";
        }
        break;
    default:
        break;
}


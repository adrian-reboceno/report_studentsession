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
 * Version metadata for the report_studentsession plugin.
 *
 * @package   report_studentsession
 * @copyright 2024, Universindad Ciudadana de Nuevo leon {@link http://www.ucnl.edu.mx/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Adrian Francisco Lozada Reboceño <adrian.lozada@ucnl.edu.mx>
 */
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/studentsession/locallib.php');
require_once($CFG->libdir . '/gradelib.php');
require_once($CFG->dirroot . '/grade/report/overview/lib.php');
require_once($CFG->dirroot . '/grade/lib.php');
require_once($CFG->dirroot . '/grade/querylib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');
require_once($CFG->dirroot . '/user/lib.php');
require($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');
use core\output\mustache_template_finder;


raise_memory_limit(MEMORY_EXTRA); // CATALYST CUSTOM.

require_login();

$urljs = new moodle_url($CFG->wwwroot.'/report/studentsession/js/selects.js');
$PAGE->requires->js($urljs, true);

$context = context_system::instance();
require_capability('report/studentsession:view', $context);
$PAGE->set_url('/report/studentsession/index.php');
$PAGE->set_pagelayout('report');
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
global $SESSION;
$action  = optional_param('action', null, PARAM_ALPHANUM);
echo $OUTPUT->header();
//REPORT_MODULECOMPLETION_ACTION_EXPORT
switch ($action) {
    case REPORT_STUDENTSESSION_ACTION_EXPORT:
        $type = optional_param('type', null, PARAM_ALPHANUM);
        if (!$type) {
            $type = 'csv';
        }
        if (isset($SESSION->quick_filter)) {
            $data   = $SESSION->quick_filter;
        }else{
            echo 'No hay datos para exportar';
        }
        $report =  report_studentsession_get_lastaccess($data);
        $reportexcel = report_excel($report);
        $excel['excel'] = $reportexcel;
        echo $OUTPUT->render_from_template('report_studentsession/donwload_report', $excel);
        break;
    case REPORT_STUDENTSESSION_ACTION_QUICK_FILTER:
        
        $form = report_studentsession_filter_form_action(null, [], true);
        if($data = $form->get_data());{

            $SESSION->quick_filter = $data;
            $report =  report_studentsession_get_lastaccess($data);
            $report['excel'] = 'excel';
            echo $OUTPUT->render_from_template('report_studentsession/get_lastaccess', $report);
        }
        break;
    default:
        //$filters = report_studentsession_get_user_filters();
        $form = report_studentsession_filter_form_action(null, [], true);
        $form->display();
        break;
}


echo $OUTPUT->footer();


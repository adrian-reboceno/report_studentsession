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


define('REPORT_STUDENTSESSION_ACTION_LOAD_FILTER', 'loadfilter');
define('REPORT_STUDENTSESSION_ACTION_QUICK_FILTER', 'quickfilter');
define('REPORT_STUDENTSESSION_ACTION_EXPORT', 'export');
define('FIXED_NUM_COLS', 6);

require_once('../../config.php');
require($CFG->libdir . '/phpspreadsheet/vendor/autoload.php');
    
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use core\notification;

use report_studentsession\forms\filters as form;


function report_studentsession_filter_form_action($filterid = null, $data = [], $quickfilter = false) {
    global $CFG, $PAGE, $USER;
    
    $customdata = [           
        'quickfilter' => $quickfilter,
        'userid'      => (int)$USER->id // For the hidden userid field.
    ];
    $customdata = array_merge($customdata, $data);
    $action     = $quickfilter ? REPORT_STUDENTSESSION_ACTION_QUICK_FILTER : REPORT_STUDENTSESSION_ACTION_LOAD_FILTER;
    $filterform = new form($PAGE->url->out(false) . '?action=' . $action, $customdata);

    return $filterform;
}

function report_studentsession_get_lastaccess($filter){
    global $DB, $USER;    

    $params = [];
    $plugin = 'report_studentsession';
    $columns = [
        get_string('id',  $plugin),
        get_string('firstname'),
        get_string('lastname'),
        get_string('email'),
        get_string('courseid',  $plugin),        
        get_string('fullname'),
        get_string('shortname'),
        get_string('category'),
        get_string('startdate'),
        get_string('enddate'),
        get_string('lastaccess'),
        get_string('courseaccesslast', $plugin),
        get_string('activity', $plugin),
        get_string('daysinactivity', $plugin),
        get_string('teacher', $plugin),
        get_string('email'),
    ];
    $datarows['thead'] = $columns;
    if(!empty($filter->period)){
      //  $params['category'] ='%/'.$filter->period.'%';
        $period = (int)$filter->period;
        $where= "(cats.path LIKE '%/{$period}%' OR cats.path LIKE '%/{$period}' )";
    }
    if(!empty($filter->program)){           
        $programa= (int)$filter->program;
        $where= "(cats.path LIKE '%/{$programa}%' OR cats.path LIKE '%/{$programa}' )";
    }
    if(!empty($filter->semester)){
        $params['category'] = (int)$filter->semester ;
        $params['parent'] = (int)$filter->semester;
        $semester = (int)$filter->semester;
        $where= "(cats.path LIKE '%/{$semester}%' OR cats.path LIKE '%/{$semester}' )";
    }
    if(!empty($filter->course)){
        $params['course'] = $filter->course;
        $where = "c.id = :course";
    }
    if(!empty($filter->student)){
        $params ['student'] = $filter->student;  
        $where = "u.id = :student";
    }
    if(!empty($filter->activity)){      
        switch ($filter->activity) {
            case 1:
                $whereacces =  "timeaccess BETWEEN UNIX_TIMESTAMP(NOW() - INTERVAL 3 SECOND) AND UNIX_TIMESTAMP((NOW())";
                $join = "JOIN {user_lastaccess} AS last ON last.userid = u.id AND last.courseid = c.id ";
                break;
            case 2:
                $whereacces =  "timeaccess BETWEEN UNIX_TIMESTAMP(NOW() - INTERVAL - 6 SECOND) AND UNIX_TIMESTAMP(NOW() - INTERVAL 3 SECOND)";
                $join = "JOIN {user_lastaccess} AS last ON last.userid = u.id AND last.courseid = c.id ";
                break;
            case 3:
                $whereacces = "timeaccess < UNIX_TIMESTAMP(NOW() - INTERVAL 7 SECOND)";
                $join = '';
                break;
            default:
                $whereacces = '';
                $join = '';
                break;
        }
    }
    if (!empty($whereacces)) {
        $whereacces = ' AND '. $whereacces;
    } else {
        $whereacces = '';
    }

    if (empty($join)) {
        $join = '';
    } 
    if (!empty($where)) {
        $where = ' AND '. $where;
    } else {
        $where = '';
    }
    
    $sql = "SELECT
        u.id AS id,  
        u.firstname, 
        u.lastname, 
        u.email,
        c.id AS idcurso, 
        c.fullname, 
        c.shortname, 
        cats.name AS namecategory, 
        c.startdate, 
        c.enddate, 
        u.lastaccess, 
		(SELECT MAX( timeaccess) FROM {user_lastaccess}
        WHERE userid=u.id AND courseid=c.id  
        {$whereacces}        
        ) AS timeaccess, 
        (SELECT  CONCAT(p.firstname, ' ', p.lastname, '-', p.email)
            FROM {course} AS cp 
            LEFT JOIN {context} AS ctxp ON cp.id = ctxp.instanceid
            JOIN {role_assignments} AS lrap ON lrap.contextid = ctxp.id
            JOIN {user} AS p ON lrap.userid = p.id
            WHERE cp.id = c.id 
            AND lrap.roleid = 3
            LIMIT 1
        ) AS teacher
        FROM {course} AS c  
        LEFT JOIN {context} AS ctx ON c.id = ctx.instanceid
        JOIN {role_assignments} AS lra ON lra.contextid = ctx.id
        JOIN {user} AS u ON lra.userid = u.id
        JOIN {course_categories} AS cats ON c.category = cats.id
		{$join}
        
        WHERE c.category = cats.id  
        AND lra.roleid = 5 
        {$where}	
        GROUP BY u.id, u.firstname, u.lastname, u.email, c.id, c.fullname, c.shortname, cats.name, c.startdate, c.enddate, u.lastaccess";


    $data = $DB->get_records_sql($sql, $params);

    $day  =  date("Y-m-d H:i:s");
    $daytotal = 0;
    $day = new DateTime (substr($day,0,10));
    $daysinactivitystudents = get_config('report_ucnl', 'daysinactivitystudents');

    foreach ($data as $key => $value) {
        $daytotal = 0;
        $row['id'] = $value->id;
        $row['firstname'] = $value->firstname;
        $row['lastname'] = $value->lastname;
        $row['email'] = $value->email;
        $row['idcurso'] = $value->idcurso;
        $row['fullname'] = $value->fullname;
        $row['shortname'] = $value->shortname;
        $row['namecategory'] = $value->namecategory;
        $row['startdate'] =  date('Y-m-d H:i:s', $value->startdate);
        $row['enddate'] =  date('Y-m-d H:i:s', $value->enddate);
        $row['lastaccess'] = $value->lastaccess ? date('Y-m-d H:i:s', $value->lastaccess) : get_string('notentered', 'report_studentsession');    
        $row['timeaccess'] = $value->timeaccess ? date('Y-m-d H:i:s', $value->timeaccess) : get_string('notentered', 'report_studentsession');
        $fechafinaliza = new DateTime (substr(date('Y-m-d H:i:s', $value->timeaccess), 0, 10));                         
        $diff = $day->diff($fechafinaliza);
        if(empty($value->timeaccess)){
            $row['activity'] = get_string('inactive','report_studentsession');
            $qualified = get_config('report_ucnl', 'notqualified');
            $row['qualified'] = $qualified;
            $row['diff'] = 0;
        }else{
            $daytotal = $diff->days;            
            if($daytotal >= $daysinactivitystudents){
                $row['activity'] = get_string('inactive','report_studentsession');
                $qualified = get_config('report_ucnl', 'notqualified');
                $row['qualified'] = $qualified;
            }elseif($daytotal> 3 && $daytotal <= 6){
                $row['activity'] = get_string('active','report_studentsession');
                $qualified = get_config('report_ucnl', 'mediumqualified');
                $row['qualified'] = $qualified;
            }elseif($daytotal >= 1 && $daytotal <= 3){
                $row['activity'] = get_string('veryactive','report_studentsession');
                $qualified = get_config('report_ucnl', 'qualified');
                $row['qualified'] = $qualified;
            }elseif($daytotal == 0){
                $row['activity'] = get_string('veryactive','report_studentsession');
                $qualified = get_config('report_ucnl', 'qualified');
                $row['qualified'] = $qualified;
            }
            $row['diff'] = $daytotal;
        }
        if($value->teacher == null){
            $row['teacher'] = get_string('notteacher','report_studentsession');
            $row['mailteacher'] = get_string('notmail','report_studentsession');
        }else{
            $arrayTeacher = explode('-', $value->teacher);
            list($teacher, $mailteacher) = $arrayTeacher;
            $row['teacher'] = $teacher;
            $row['mailteacher'] = $mailteacher;
        }
        $datarows['rows'][] = $row;
        $row['activity'] = null;
        $row['qualified'] = null;
    }
    return $datarows;
}

function report_excel($data){
    global $CFG, $DB;
    $now = time();
    $spread = new Spreadsheet();

    $spread->getActiveSheet()->getStyle('A1:P1')->getFont()->setBold(true);
    foreach (range('A','P') as $col) {
        $spread->getActiveSheet()->getColumnDimension($col)->setAutoSize(true);
    }
    foreach($data['thead'] as $key => $value){
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow($key+1, 1, $value);
    }
    foreach($data['rows'] as $key => $value){
        if($value['qualified']){
            $spread->getActiveSheet()->getStyle('L'. $key+2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB( strtoupper(str_replace('#','',$value['qualified'])));
            $spread->getActiveSheet()->getStyle('M'. $key+2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB( strtoupper(str_replace('#','',$value['qualified'])));
            $spread->getActiveSheet()->getStyle('N'. $key+2)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB( strtoupper(str_replace('#','',$value['qualified'])));
        }        
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(1, $key+2, $value['id']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(2, $key+2, $value['firstname']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(3, $key+2, $value['lastname']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(4, $key+2, $value['email']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(5, $key+2, $value['idcurso']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(6, $key+2, $value['fullname']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(7, $key+2, $value['shortname']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(8, $key+2, $value['namecategory']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(9, $key+2, $value['startdate']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(10, $key+2, $value['enddate']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(11, $key+2, $value['lastaccess']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(12, $key+2, $value['timeaccess']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(13, $key+2, $value['activity']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(14, $key+2, $value['diff']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(15, $key+2, $value['teacher']);
        $spread->setActiveSheetIndex(0)->setCellValueByColumnAndRow(16, $key+2, $value['mailteacher']);
    }
    $spread->getActiveSheet()->setTitle(get_string('pluginname', 'report_studentsession'));
    $writer = new Xlsx($spread);
    $filename = 'report_'.$now.'.xlsx'; 
    $filename = 'export/'.$filename;  
    $writer->save($filename);
    return $filename;
}



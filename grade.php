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
 * Redirects the user to either a questionnaire or to the questionnaire report 
 * by click on grader report page
 *
 * @package   mod_questionnaire
 * @copyright 2013 onwards Joseph Rézeau  email moodle@rezeau.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

/**
 * Require config.php
 */

require_once("../../config.php");
require_once($CFG->dirroot.'/mod/questionnaire/questionnaire.class.php');

$id = required_param('id', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$cm = get_coursemodule_from_id('questionnaire', $id, 0, false, MUST_EXIST);
$instance = optional_param('instance', false, PARAM_INT);   // Questionnaire ID.
$action = optional_param('action', 'vall', PARAM_ALPHA);
$type = optional_param('type', '', PARAM_ALPHA);
$byresponse = optional_param('byresponse', false, PARAM_INT);
$individualresponse = optional_param('individualresponse', false, PARAM_INT);
$currentgroupid = optional_param('group', 0, PARAM_INT); // Groupid.
$user = optional_param('user', '', PARAM_INT);
$groupname = '<strong>'.get_string('allparticipants').'</strong>';
if (! $questionnaire = $DB->get_record("questionnaire", array("id" => $cm->instance))) {
    print_error('invalidcoursemodule');
}
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
require_login($course, false, $cm);
$PAGE->set_url('/mod/questionnaire/grade.php', array('id' => $cm->id));

if (has_capability('mod/questionnaire:readallresponseanytime', context_module::instance($cm->id))) {
    //    redirect('report.php?instance='.$questionnaire->id);
    $questionnaire = new questionnaire(0, $questionnaire, $course, $cm);
    $resps = $questionnaire->get_responses();
    // Add renderer and page objects to the questionnaire object for display use.
    $questionnaire->add_renderer($PAGE->get_renderer('mod_questionnaire'));
    $questionnaire->add_page(new \mod_questionnaire\output\reportpage());
    
    $PAGE->set_title(get_string('questionnairereport', 'questionnaire'));
    $PAGE->set_heading(format_string($course->fullname));
    echo $questionnaire->renderer->header();
    // Print the tabs.
    $SESSION->questionnaire->current_tab = 'individualresp';
    
    
    
    
    
    $currentgroupid = 0;
    $context = context_module::instance($cm->id);
      include('tabs.php');
    
    $respinfo = '';
    $respinfo .= $questionnaire->renderer->box_start();
    $respinfo .= $questionnaire->renderer->help_icon('viewindividualresponse', 'questionnaire').'&nbsp;';
    $respinfo .= get_string('viewindividualresponse', 'questionnaire').' <strong> : '.$groupname.'</strong>';
    $respinfo .= $questionnaire->renderer->box_end();
    $questionnaire->page->add_to_page('respondentinfo', $respinfo);
    $response = $questionnaire->get_responses($userid,$currentgroupid);
    $resp = current($response);
    $rid = $resp->id;
    
    $charttype = $questionnaire->survey->chart_type;
    if ($charttype) {
        $PAGE->requires->js('/mod/questionnaire/javascript/RGraph/RGraph.common.core.js');
        
        switch ($charttype) {
            case 'bipolar':
                $PAGE->requires->js('/mod/questionnaire/javascript/RGraph/RGraph.bipolar.js');
                break;
            case 'hbar':
                $PAGE->requires->js('/mod/questionnaire/javascript/RGraph/RGraph.hbar.js');
                break;
            case 'radar':
                $PAGE->requires->js('/mod/questionnaire/javascript/RGraph/RGraph.radar.js');
                break;
            case 'rose':
                $PAGE->requires->js('/mod/questionnaire/javascript/RGraph/RGraph.rose.js');
                break;
            case 'vprogress':
                $PAGE->requires->js('/mod/questionnaire/javascript/RGraph/RGraph.vprogress.js');
                break;
        }
    }


    $questionnaire->survey_results_navbar_alpha($rid, $currentgroupid, $cm, $byresponse);
    //$questionnaire->view_response($rid, 0, false, $resp, true, true, false, $currentgroupid);
    $questionnaire->view_response($rid, '', false, $resps, true, true, false, $currentgroupid);
    echo $questionnaire->renderer->render($questionnaire->page);
    
    // Finish the page.
    echo $questionnaire->renderer->footer($course);
    
    
} else {
    redirect('view.php?id='.$cm->id);
}

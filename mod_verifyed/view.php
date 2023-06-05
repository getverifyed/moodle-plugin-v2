<?php

// Code for loading the course context, checking user permissions, and handling the request for the certificate issuance

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/report/user/lib.php');
require_once($CFG->libdir.'/grade/grade_item.php');
require_once($CFG->libdir.'/grade/grade_grade.php');

$id = required_param('id', PARAM_INT); // Course Module ID

// Load the course module and context
$cm = get_coursemodule_from_id('verifyed', $id, 0, true);
$context = context_module::instance($cm->id);

// Check if the user has the capability to view this page
require_login($cm->course, true, $cm);
require_capability('mod/verifyed:view', $context);

// Get VerifyEd course ID from the database
$verifyed_course_id = $DB->get_field('verifyed_course_map', 'verifyed_course_id', array('course_id' => $cm->course));

// Gets the learner's course grade
$course_item = grade_item::fetch_course_item($cm->course);
$grades = grade_grade::fetch_users_grades($course_item, array($USER->id), true);
$course_grade = isset($grades[0]) ? $grades[0] : null;

// Check and format the learner's outcome
$outcome = isset($course_grade) && isset($course_grade->grades) ? (string) $course_grade->grades[$USER->id]->grade : "Complete";

global $DB;

// Retrieve the template ID from the verifyed instance
$template_id = $DB->get_field('verifyed', 'templateid', array('course' => $cm->course));

// Check if the user has already requested a certificate
if (!verifyed_has_certificate($USER->id, $cm->course)) {
    // Prepare the certificate data
    $certificate_data = array(
        "templateId" => $template_id,
        "courseId" => (string) $verifyed_course_id,
        "learningPathwayId" => null,
        "public" => false,
        "studentData" => array(
            array(
                "email" => $USER->email,
                "name" => fullname($USER),
                "outcome" => $outcome,
                "completionDate" => date("Y-m-d\TH:i:s\Z"),
                "additionalInfo" => array()
            )
        )
    );

    // Issue the certificate by calling VerifyEd API
    verifyed_issue_certificate($certificate_data);

} else {
    // Display a message that the user already has a certificate
    echo html_writer::tag('p', get_string('message_already_issued', 'mod_verifyed'));
}
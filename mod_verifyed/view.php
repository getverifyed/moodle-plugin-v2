<?php
// Code for loading the course context, checking user permissions, and handling the request for the certificate issuance
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/report/user/lib.php');
require_once($CFG->libdir.'/grade/grade_item.php');
require_once($CFG->libdir.'/grade/grade_grade.php');

$id = required_param('id', PARAM_INT);

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

$course_grade = isset($grades[$USER->id]) ? $grades[$USER->id] : null;

// Check and format the learner's outcome
$outcome = isset($course_grade) && isset($course_grade->finalgrade) ? (string) $course_grade->finalgrade : null;

// Retrieve the template ID from the verifyed instance
$template_id = $DB->get_field('verifyed', 'templateid', array('course' => $cm->course));

// Check if the user has already requested a certificate
if (!verifyed_has_certificate($USER->id, $cm->course)) {
    // Here you show the button or message based on the user's eligibility
    if ($outcome != null) {
        // Show a button to the user to claim their certificate
        $claim_certificate_url = new moodle_url('/mod/verifyed/claim_certificate.php',
            array('cmid' => $cm->id, 'course' => $cm->course, 'sesskey' => sesskey(), 'outcome' => $outcome));
        redirect($claim_certificate_url);
    } else {
        // Show a message that the user is not eligible yet
        redirect(new moodle_url('/course/view.php', array('id' => $cm->course)), get_string('messagenotready', 'mod_verifyed'));
    }
} else {
    // Display a message that the user already has a certificate
    redirect(new moodle_url('/course/view.php', array('id' => $cm->course)), get_string('messagealreadyissued', 'mod_verifyed'));
}
<?php

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$cmid = required_param('cmid', PARAM_INT);
$courseid = required_param('course', PARAM_INT);
$sesskey = required_param('sesskey', PARAM_ALPHANUM);
$outcome = required_param('outcome', PARAM_TEXT);

require_sesskey($sesskey);
require_login($courseid);

// Load the course module and context
$cm = get_coursemodule_from_id('verifyed', $cmid, 0, true);
$context = context_module::instance($cm->id);

// Check if the user has the capability to view this page
require_capability('mod/verifyed:view', $context);

// Get VerifyEd course ID from the database
$verifyed_course_id = $DB->get_field('verifyed_course_map', 'verifyed_course_id', array('course_id' => $cm->course));

if ($outcome != null) {
    // Retrieve the template ID from the verifyed instance
    $template_id = $DB->get_field('verifyed', 'templateid', array('course' => $cm->course));

    // Request a certificate by calling the verifyed_request_certificate function
    $certificate_issued = verifyed_request_certificate($cm, $verifyed_course_id, $template_id, $USER, $outcome);

    if ($certificate_issued) {
        // Store the certificate record in the verifyed_certificates database table
        $certificate_record = new stdClass();
        $certificate_record->userid = $USER->id;
        $certificate_record->courseid = $cm->course;
        $certificate_record->timecreated = time();
        $DB->insert_record('verifyed_certificates', $certificate_record);
    
        // Redirects the user with a success message
        redirect(new moodle_url('/course/view.php', array('id' => $cm->course)), get_string('messagesuccess', 'mod_verifyed'));
    } else {
        // Redirect user back to the view page with an error message
        redirect(new moodle_url('/mod/verifyed/view.php', array('id' => $cmid)), get_string('messagenotready', 'mod_verifyed'));
    }
} else {
    // Redirect user back to the view page with an error message
    redirect(new moodle_url('/mod/verifyed/view.php', array('id' => $cmid)), get_string('messagenotready', 'mod_verifyed'));
}
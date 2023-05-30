<?php

// Code for loading the course context, checking user permissions, and handling the request for the certificate issuance

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$gid = required_param('gid', PARAM_INT); // Course Module ID

// Load the course module and context
if ($cm = get_coursemodule_from_id('verifyed', $gid, 0, true)) {
    $context = context_module::instance($cm->id);
}

// Check if the user has the capability to view this page
require_login($cm->course, true, $cm);
require_capability('mod/verifyed:view', $context);

// Check if the user has already requested a certificate
if (!verifyed_has_certificate($USER->id, $cm->course)) {
    // Issue the certificate by calling VerifyEd API
    $certificate_data = array( /* Required certificate data from Moodle */ );
    verifyed_issue_certificate($certificate_data);
    // Display a success message
    echo html_writer::tag('p', get_string('message_success', 'mod_verifyed'));
} else {
    // Display a message that the user already has a certificate
    echo html_writer::tag('p', get_string('message_already_issued', 'mod_verifyed'));
}
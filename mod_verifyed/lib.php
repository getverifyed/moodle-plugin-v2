<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Add a new instance of VerifyEd to course
 *
 * @param stdClass $verifyed
 * @return int
 */
function verifyed_add_instance($verifyed) {
    global $DB;

    // Save plugin instance to database
    $verifyed->id = $DB->insert_record('verifyed', $verifyed);

    // Get course data from Moodle
    $moodle_course = $DB->get_record('course', array('id' => $verifyed->course), 'fullname, shortname, summary');

    // Set course data based on requirements
    $course_data = array(
        "name" => $moodle_course->fullname,
        "information" => $moodle_course->summary,
        "courseskills" => [],
        "validity" => array(
            "validityNumber" => 0,
            "validityLength" => "year(s)"
        ),
        "durationValues" => array(
            "durationNumber" => 0,
            "durationLength" => "year(s)"
        ),
        "courseLink" => "",
        "courseCode" => $moodle_course->shortname,
        "referencedCourses" => [],
        "additionalInfo" => []
    );

    // Create a course in VerifyEd
    $verifyed_course_id = verifyed_create_course($course_data);

    // Save VerifyEd course ID to database
    $verifyed_course_map = new stdClass();
    $verifyed_course_map->course_id = $verifyed->course;
    $verifyed_course_map->verifyed_course_id = $verifyed_course_id;
    $DB->insert_record('verifyed_course_map', $verifyed_course_map);

    // Save plugin instance to database
    $verifyed->templateid = $verifyed->templateid;
    $verifyed->id = $DB->insert_record('verifyed', $verifyed);

    return $verifyed->id;
}

/**
 * Update an existing VerifyEd instance
 *
 * @param stdClass $verifyed
 * @return bool
 */
function verifyed_update_instance($verifyed) {
    global $DB;

    // Update plugin instance in the database
    $DB->update_record('verifyed', $verifyed);

    return true;
}

/**
 * Create a course in VerifyEd
 *
 * @param array $course_data
 * @return int
 */
function verifyed_create_course($course_data) {
    // Prepare JSON data for the API request
    $data = json_encode($course_data);

    // Initialize cURL session
    $ch = curl_init('https://api.verifyed.io/external/courses?apiKey=' . urlencode(get_config('verifyed', 'apikey')) . '&type=institution');

    // Set cURL options
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
    );

    // Execute cURL request and close the session
    $response = curl_exec($ch);
    curl_close($ch);

    // Decode the JSON response
    $result = json_decode($response, true);

    // Return the VerifyEd course ID
    return $result['data']['id'];
}

/**
 * Issue a certificate in VerifyEd
 *
 * @param array $certificate_data
 * @return void
 */
function verifyed_issue_certificate($certificate_data) {
    // Prepare JSON data for the API request
    $data = json_encode($certificate_data);

    // Initialize cURL session
    $ch = curl_init('https://api.verifyed.io/external/issue-credentials?apiKey=' . urlencode(get_config('verifyed', 'apikey')) . '&type=partner');

    // Set cURL options
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data))
    );

    // Execute cURL request and close the session
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Decode the JSON response
    $result = json_decode($response, true);

    // Check if the certificate has been issued successfully
    if ($http_status === 200 && isset($result['learnerData']) && !empty($result['learnerData'])) {
        // Display success message
        echo html_writer::tag('p', 'Your certificate has been issued. Check your emails for a link to see it.');
    } else {
        // Display error message
        echo html_writer::tag('p', 'Sorry, your certificate could not be issued right now. You should contact your institution to resolve the problem and claim your certificate.');
    }
}

/**
 * Check whether a student already has a certificate issued
 *
 * @param int $user_id
 * @param int $course_id
 * @return bool
 */
function verifyed_has_certificate($user_id, $course_id) {
    global $DB;

    // Query the database to check if a certificate has already been issued to the student
    $sql = "SELECT COUNT(*) FROM {verifyed_certificates} WHERE userid = ? AND courseid = ?";
    $params = array($user_id, $course_id);
    $count = $DB->count_records_sql($sql, $params);

    return $count > 0;
}

/**
 * Function for adding plugin settings to the course administration block
 *
 * @param settings_navigation $settingsnav
 * @param context_course $context
 */
function verifyed_extend_settings_navigation(settings_navigation $settingsnav, context_course $context) {
    if (has_capability('moodle/course:update', $context)) {
        $url = new moodle_url('/mod/verifyed/settings.php', array('contextid' => $context->id));
        $node = navigation_node::create(get_string('pluginadministration', 'mod_verifyed'), $url, navigation_node::TYPE_SETTING,
            null, null, new pix_icon('t/edit', ''));
        $settingsnav->add_node($node, 'courseadmin');
    }
}
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
    $verifyed_course_response = verifyed_create_course($course_data);

    // If the VerifyEd course creation failed, throw an exception
    if ($verifyed_course_response['result'] === false) {
        throw new moodle_exception('error_verifyed_course_creation', 'mod_verifyed');
    }

    // Save VerifyEd course ID to database
    $verifyed_course_map = new stdClass();
    $verifyed_course_map->course_id = $verifyed->course;
    $verifyed_course_map->verifyed_course_id = $verifyed_course_response['message'];

    $DB->insert_record('verifyed_course_map', $verifyed_course_map);

    // Save plugin instance to database
    $verifyed->templateid = $verifyed->templateid;
    $verifyed->name = $verifyed->name;

    return $verifyed->id;
}

/**
 * Update an existing VerifyEd instance
 *
 * @param stdClass $verifyed
 * @return bool
 */
function verifyed_update_instance($verifyed) {
    global $DB, $CFG;

    require_once($CFG->dirroot.'/lib/filelib.php');

    // Set the id property
    $verifyed->id = $verifyed->instance;

    // Process intro editor fields
    $draftitemid = file_get_submitted_draft_itemid('introeditor');
    file_prepare_draft_area(
        $draftitemid,
        context_module::instance($verifyed->coursemodule)->id,
        'mod_verifyed',
        'intro',
        0,
        array('subdirs' => 0, 'maxfiles' => 0)
    );
    $verifyed->intro = file_save_draft_area_files(
        $draftitemid,
        context_module::instance($verifyed->coursemodule)->id,
        'mod_verifyed',
        'intro',
        0,
        array('subdirs' => 0, 'maxfiles' => 0),
        $verifyed->introeditor['text']
    );
    $verifyed->introformat = $verifyed->introeditor['format'];

    // Update plugin instance in the database
    $DB->update_record('verifyed', $verifyed);

    // Update intro files
    file_postupdate_standard_editor(
        $verifyed,
        'intro',
        array('subdirs' => 0, 'maxfiles' => 0),
        context_module::instance($verifyed->coursemodule)->id,
        'mod_verifyed',
        'intro',
        0
    );

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
    $ch = curl_init('https://api.verifyed.io/external/courses?apiKey=' . get_config('mod_verifyed', 'apikey') . '&type=institution');

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

    // Check if the API request was successful
    if ($http_status === 201 && isset($result['data']) && isset($result['data']['id'])) {
        // Return the VerifyEd course ID
        return array(
            "result" => true,
            "message" => $result['data']['id']
        );
    } else {
        // Log error message and return false
        return array(
            "result" => false,
            "message" => "VerifyEd API Error: Failed to create the course. Response: " . print_r($result, true)
        );
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
function verifyed_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $contextnode) {
    // Check whether we can get a context_course instance from the cm node.
    // If we can, display the settings navigation.
    $cmnode = $contextnode->get($contextnode->key, 'course');
    if ($cmnode && $context = context_course::instance($cmnode->key)) {        
        if (has_capability('moodle/course:update', $context)) {
            $url = new moodle_url('/mod/verifyed/settings.php', array('contextid' => $context->id));
            $node = navigation_node::create(get_string('pluginadministration', 'mod_verifyed'), $url, navigation_node::TYPE_SETTING,
                null, null, new pix_icon('t/edit', ''));
            $settingsnav->add_node($node, 'courseadmin');
        }
    }
}

function verifyed_request_certificate($cm, $verifyed_course_id, $template_id, $USER, $outcome) {
    global $DB;

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
            ),
        ),
    );

    // Issue the certificate by calling VerifyEd API
    $certificate_issued = verifyed_issue_certificate($certificate_data);

    return $certificate_issued;
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
    $ch = curl_init('https://api.verifyed.io/external/issue-credentials?apiKey=' . get_config('mod_verifyed', 'apikey') . '&type=institution');

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
    if ($http_status === 201 && isset($result['learnerData']) && !empty($result['learnerData'])) {
        // Display success message
        echo html_writer::tag('p', 'Your certificate has been issued. Check your emails for a link to see it.');
        return true;
    } else {
        // Display error message
        echo html_writer::tag('p', 'Sorry, your certificate could not be issued right now. You should contact your institution to resolve the problem and claim your certificate.');
        return false;
    }
}

/**
 * Returns the custom renderer class for mod_verifyed.
 *
 * @param stdClass $page The page object to get the renderer.
 * @return mod_verifyed_renderer The custom renderer instance.
 */
function verifyed_get_renderer($page) {
    return $page->get_renderer('mod_verifyed');
}

/**
 * Deletes an instance of the verifyed module
 *
 * @param int $id The instance ID of the verifyed module
 * @return bool true on success, false otherwise
 */
function verifyed_delete_instance($id) {
    global $DB;

    if (!$verifyed_instance = $DB->get_record('verifyed', array('id' => $id))) {
        return false;
    }

    // Delete related data from the verifyed_course_map table
    $DB->delete_records('verifyed_course_map', array('course_id' => $verifyed_instance->course));

    // Delete the verifyed instance
    $DB->delete_records('verifyed', array('id' => $verifyed_instance->id));

    return true;
}

/**
 * Get certificate templates from VerifyEd API
 *
 * @return array
 */
function get_certificate_templates() {
    $api_key = get_config('mod_verifyed', 'apikey');

    if (empty($api_key)) {
        return array();
    }

    $response = make_get_request('https://api.verifyed.io/external/certificates/templates', $api_key);

    if ($response['result'] === false) {
        return array();
    }

    $templates = $response['data'];

    $template_list = array();
    if (is_array($templates) && count($templates) > 0) {
        foreach ($templates as $template) {
            $template_list[$template['id']] = $template['name'];
        }
    }
    return $template_list;
}

/**
 * Makes a GET request to a specific URL and returns an associative array with the response data.
 *
 * @param string $url
 * @param string $api_key
 * @return array
 */
function make_get_request($url, $api_key) {
    $ch = curl_init($url . '?apiKey=' . $api_key . '&type=institution');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_status === 200) {
        $result = json_decode($response, true);
        return array(
            'result' => true,
            'data' => $result['data'],
        );
    } else {
        return array(
            'result' => false,
            'message' => 'API Error: Failed to get certificate templates. Response: ' . print_r($result, true),
        );
    }
}
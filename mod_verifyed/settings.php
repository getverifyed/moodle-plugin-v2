<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Add a setting as textarea for the API key
    $settings->add(new admin_setting_configtextarea('mod_verifyed/apikey',
        get_string('apikey', 'mod_verifyed'), get_string('apikey_desc', 'mod_verifyed'), '', PARAM_RAW, '60', '4'));
}
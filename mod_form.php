<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_verifyed_mod_form extends moodleform_mod {

    function definition() {

        $mform = $this->_form;

        // Add a field for the template ID
        $template_options = get_certificate_templates();
        if (!empty($template_options)) {
            $mform->addElement('select', 'templateid', get_string('templateid', 'mod_verifyed'), $template_options);
            $mform->setType('templateid', PARAM_INT);
            $mform->addRule('templateid', null, 'required', null, 'client');
        } else {
            $mform->addElement('static', 'templateid_error', get_string('templateid', 'mod_verifyed'), get_string('templateidnotfound', 'mod_verifyed'));
        }
        // $mform->addElement('text', 'templateid', get_string('templateid', 'mod_verifyed'));
        // $mform->setType('templateid', PARAM_INT);
        // $mform->addRule('templateid', null, 'required', null, 'client');

        // Add a field for the instance name
        $mform->addElement('text', 'name', get_string('name', 'mod_verifyed'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Add standard course module elements
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}
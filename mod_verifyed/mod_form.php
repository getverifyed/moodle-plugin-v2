<?php

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_verifyed_mod_form extends moodleform_mod {

    function definition() {

        $mform = $this->_form;

        // Add a field for the template ID
        $mform->addElement('text', 'templateid', get_string('templateid', 'mod_verifyed'));
        $mform->setType('templateid', PARAM_INT);
        $mform->addRule('templateid', null, 'required', null, 'client');

        // Add standard course module elements
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}
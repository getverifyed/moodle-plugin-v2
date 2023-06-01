<?php

defined('MOODLE_INTERNAL') || die();

class mod_verifyed_renderer extends plugin_renderer_base {

    /**
     * Returns the URL of the activity icon.
     *
     * @return moodle_url The custom icon URL.
     */
    public function get_icon_url() {
        global $CFG;
        $iconurl = new moodle_url($CFG->wwwroot . '/mod/verifyed/pix/icon.svg');
        return $iconurl;
    }

    /**
     * Returns the custom activity icon for the course page.
     *
     * @param cm_info $cm Course module info.
     * @return string HTML for the custom activity icon.
     */
    public function course_mod_icon(cm_info $cm) {
        $iconurl = $this->get_icon_url();
        $icon = html_writer::empty_tag('img', array('src' => $iconurl, 'class' => 'iconlarge activityicon', 'alt' => 'VerifyEd logo'));
        return $icon;
    }
}
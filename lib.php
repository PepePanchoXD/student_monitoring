<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Add settings/navigation hooks here if required.
 * Kept minimal; the plugin exposes index.php UI and locallib functions.
 */
function local_student_monitoring_extend_navigation_course($navigation, $course, $context) {
    // Optionally add a link for teachers/managers to the report.
    if (has_capability('local/student_monitoring:viewreport', $context)) {
        $url = new moodle_url('/local/student_monitoring/index.php', ['id' => $course->id]);
        $navigation->add(get_string('pluginname', 'local_student_monitoring'), $url, navigation_node::TYPE_CUSTOM, null, 'local_student_monitoring');
    }
}

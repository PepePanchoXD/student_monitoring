<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/local/student_monitoring/locallib.php');

$id = required_param('id', PARAM_INT); // course id
$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_login($course);

$context = context_course::instance($course->id);
require_capability('local/student_monitoring:viewreport', $context);

$PAGE->set_url('/local/student_monitoring/index.php', ['id' => $course->id]);
$PAGE->set_title(get_string('pluginname', 'local_student_monitoring') . ' - ' . format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('activitytotals', 'local_student_monitoring'));

$rows = local_student_monitoring_get_course_report_rows($course->id);

echo html_writer::start_tag('table', ['class' => 'generaltable']);
echo html_writer::tag('thead', html_writer::tag('tr',
    html_writer::tag('th', get_string('cmid', 'local_student_monitoring')) .
    html_writer::tag('th', get_string('activity', 'local_student_monitoring')) .
    html_writer::tag('th', get_string('totaltime', 'local_student_monitoring'))
));
echo html_writer::start_tag('tbody');

foreach ($rows as $r) {
    $timehuman = format_time($r['totalduration']);
    echo html_writer::tag('tr',
        html_writer::tag('td', s($r['cmid'])) .
        html_writer::tag('td', s($r['modname'])) .
        html_writer::tag('td', s($timehuman))
    );
}

echo html_writer::end_tag('tbody');
echo html_writer::end_tag('table');

echo $OUTPUT->footer();

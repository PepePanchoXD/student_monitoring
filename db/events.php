<?php
defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\mod_forum\event\course_module_viewed', // example module event namespace; generic event subscriber below
        'callback'    => 'local_student_monitoring_observer::course_module_viewed',
        'includefile' => '/local/student_monitoring/locallib.php',
    ],
    [
        'eventname'   => '\core\event\course_module_viewed',
        'callback'    => 'local_student_monitoring_observer::course_module_viewed',
        'includefile' => '/local/student_monitoring/locallib.php',
    ],
    [
        'eventname'   => '\core\event\user_loggedout',
        'callback'    => 'local_student_monitoring_observer::user_loggedout',
        'includefile' => '/local/student_monitoring/locallib.php',
    ],
];

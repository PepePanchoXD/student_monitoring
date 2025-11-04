<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'local/student_monitoring:viewreport' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
        ],
        'riskbitmask' => RISK_PERSONAL,
    ],

    // Allow students to view only their own monitoring data.
    'local/student_monitoring:viewown' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,
        ],
        'riskbitmask' => RISK_PERSONAL,
    ],
];

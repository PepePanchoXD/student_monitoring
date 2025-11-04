<?php
defined('MOODLE_INTERNAL') || die();

class local_student_monitoring_observer {

    /**
     * course_module_viewed event handler.
     * Starts a new session for the viewed module, and closes any other open sessions for the user.
     *
     * @param \core\event\base $event
     */
    public static function course_module_viewed(\core\event\base $event) {
        global $DB;

        $userid = $event->userid ?? $event->relateduserid ?? null;
        $courseid = $event->courseid ?? 0;
        $contextinstanceid = $event->contextinstanceid ?? 0; // usually cmid

        if (empty($userid) || empty($courseid)) {
            return;
        }

        $cmid = $contextinstanceid;
        $modname = $event->other['modulename'] ?? '';

        // Close any open sessions for this user (different cmid).
        local_student_monitoring_close_all_open_sessions($userid);

        // Start a new session for this cmid.
        local_student_monitoring_start_session($userid, $courseid, $cmid, $modname);
    }

    /**
     * user_loggedout event handler.
     * Closes any open sessions for the user.
     *
     * @param \core\event\base $event
     */
    public static function user_loggedout(\core\event\base $event) {
        $userid = $event->userid ?? null;
        if ($userid) {
            local_student_monitoring_close_all_open_sessions($userid);
        }
    }
}

/**
 * Start a monitoring session for a user on a course module.
 *
 * @param int $userid
 * @param int $courseid
 * @param int $cmid
 * @param string $modname
 * @return int inserted record id
 */
function local_student_monitoring_start_session($userid, $courseid, $cmid = 0, $modname = '') {
    global $DB;

    $now = time();

    $record = new stdClass();
    $record->userid = $userid;
    $record->courseid = $courseid;
    $record->cmid = $cmid ?: 0;
    $record->modname = $modname ?: '';
    // populate student and activity names (store snapshot)
    list($studentname, $activityname) = local_student_monitoring_resolve_names($userid, $cmid, $modname);
    $record->studentname = $studentname;
    $record->activityname = $activityname;
    $record->timestart = $now;
    $record->timeend = 0;
    $record->duration = 0;
    $record->timemodified = $now;

    return $DB->insert_record('local_studentmonitoring', $record);
}

/**
 * Close an open session (set timeend and duration).
 *
 * @param int $recordid
 * @param int|null $timeend
 * @return bool
 */
function local_student_monitoring_close_session($recordid, $timeend = null) {
    global $DB;

    $timeend = $timeend ?? time();
    if (!$record = $DB->get_record('local_studentmonitoring', ['id' => $recordid])) {
        return false;
    }
    if (!empty($record->timeend)) {
        return true; // already closed
    }
    $duration = max(0, $timeend - $record->timestart);

    $record->timeend = $timeend;
    $record->duration = $duration;
    $record->timemodified = time();
    // ensure names are present (if not populated at insert time)
    if (empty($record->studentname) || empty($record->activityname)) {
        list($studentname, $activityname) = local_student_monitoring_resolve_names($record->userid, $record->cmid, $record->modname ?? '');
        if (!empty($studentname)) {
            $record->studentname = $studentname;
        }
        if (!empty($activityname)) {
            $record->activityname = $activityname;
        }
    }

    return $DB->update_record('local_studentmonitoring', $record);
}

/**
 * Close all open sessions for a user (optionally filtered by course).
 *
 * @param int $userid
 * @param int|null $courseid
 */
function local_student_monitoring_close_all_open_sessions($userid, $courseid = null) {
    global $DB;

    $sql = "userid = :userid AND timeend = 0";
    $params = ['userid' => $userid];
    if ($courseid !== null) {
        $sql .= " AND courseid = :courseid";
        $params['courseid'] = $courseid;
    }

    $opens = $DB->get_records_select('local_studentmonitoring', $sql, $params);
    $now = time();
    foreach ($opens as $open) {
        $open->timeend = $now;
        $open->duration = max(0, $now - $open->timestart);
        $open->timemodified = $now;
        // ensure names are present
        if (empty($open->studentname) || empty($open->activityname)) {
            list($studentname, $activityname) = local_student_monitoring_resolve_names($open->userid, $open->cmid, $open->modname ?? '');
            if (!empty($studentname)) {
                $open->studentname = $studentname;
            }
            if (!empty($activityname)) {
                $open->activityname = $activityname;
            }
        }
        $DB->update_record('local_studentmonitoring', $open);
    }
}

/**
 * Resolve student full name and activity name for storage.
 * Returns array [$studentname, $activityname].
 * Attempts several fallbacks and tolerates missing data.
 *
 * @param int $userid
 * @param int $cmid
 * @param string $modname
 * @return array [studentname, activityname]
 */
function local_student_monitoring_resolve_names($userid, $cmid = 0, $modname = '') {
    global $DB;

    $studentname = '';
    $activityname = '';

    // Resolve student full name.
    if (!empty($userid) && $user = $DB->get_record('user', ['id' => $userid], '*', IGNORE_MISSING)) {
        // fullname() is provided by core and formats the name according to site settings.
        $studentname = fullname($user);
    }

    // Resolve activity name from cmid if possible.
    if (!empty($cmid)) {
        // Try to get course module info; fall back to DB lookup if necessary.
        if (function_exists('get_coursemodule_from_id')) {
            $cm = get_coursemodule_from_id(null, $cmid, 0, false, IGNORE_MISSING);
        } else {
            $cm = null;
        }
        if ($cm && !empty($cm->modname) && !empty($cm->instance)) {
            $activityname = $DB->get_field($cm->modname, 'name', ['id' => $cm->instance], IGNORE_MISSING) ?: '';
        } else {
            // As a last resort, use provided modname string (may be empty).
            $activityname = $modname ?: '';
        }
    } else {
        // If no cmid, use modname string if present.
        $activityname = $modname ?: '';
    }

    return [$studentname, $activityname];
}

/**
 * Get aggregated total time spent per cmid (activity) for a course.
 *
 * @param int $courseid
 * @return array keyed by cmid with ['modname'=>..., 'totalduration'=>..., 'sessions'=>...]
 */
function local_student_monitoring_get_activity_totals($courseid) {
    global $DB;

        // Sum finished sessions. Prefer stored activityname (snapshot) when available.
        $sql = "SELECT cmid, COALESCE(MAX(activityname), MAX(modname)) AS activityname, SUM(duration) as totalduration, COUNT(*) as sessions
                            FROM {local_studentmonitoring}
                         WHERE courseid = :courseid
                             AND timeend > 0
                    GROUP BY cmid";
    $params = ['courseid' => $courseid];
    $results = $DB->get_records_sql($sql, $params);

        // Add ongoing sessions (approximate using now - timestart). Prefer activityname when available.
        $opensql = "SELECT cmid, COALESCE(MAX(activityname), MAX(modname)) AS activityname, SUM(:now - timestart) AS totalopen, COUNT(*) AS opensessions
                                    FROM {local_studentmonitoring}
                                 WHERE courseid = :courseid
                                     AND timeend = 0
                            GROUP BY cmid";
    $params2 = ['courseid' => $courseid, 'now' => time()];
    $openresults = $DB->get_records_sql($opensql, $params2);

    $totals = [];
    foreach ($results as $r) {
        $totals[$r->cmid] = [
            'activityname' => $r->activityname,
            'totalduration' => (int)$r->totalduration,
            'sessions' => (int)$r->sessions,
        ];
    }
    foreach ($openresults as $o) {
        if (!isset($totals[$o->cmid])) {
            $totals[$o->cmid] = [
                'activityname' => $o->activityname,
                'totalduration' => (int)$o->totalopen,
                'sessions' => (int)$o->opensessions,
            ];
        } else {
            $totals[$o->cmid]['totalduration'] += (int)$o->totalopen;
            $totals[$o->cmid]['sessions'] += (int)$o->opensessions;
        }
    }

    return $totals;
}

/**
 * Simple renderer for course report: returns array of rows [cmid, modname, totalduration (seconds)]
 *
 * @param int $courseid
 * @return array
 */
function local_student_monitoring_get_course_report_rows($courseid) {
    $totals = local_student_monitoring_get_activity_totals($courseid);
    $rows = [];
    foreach ($totals as $cmid => $info) {
        $rows[] = [
            'cmid' => $cmid,
            'activityname' => $info['activityname'] ?? ($info['modname'] ?? ''),
            'totalduration' => $info['totalduration'],
            'sessions' => $info['sessions'],
        ];
    }
    // Sort by totalduration desc.
    usort($rows, function($a, $b) {
        return $b['totalduration'] <=> $a['totalduration'];
    });
    return $rows;
}

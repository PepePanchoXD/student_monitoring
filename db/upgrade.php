<?php
// This file keeps track of upgrades to the local_student_monitoring plugin.
//
// Sometimes, changes between versions involve altering the database schema and
// this file, along with the XMLDB install file, will attempt to perform the
// necessary upgrade steps.
//
// @package    local_student_monitoring
// @copyright  2025 Your Name
// @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

defined('MOODLE_INTERNAL') || die();

function xmldb_local_student_monitoring_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // New version number (increment by 1)
    $newversion = 2025110401;

    if ($oldversion < 2025110401) {
        // Define table local_studentmonitoring to add fields studentname and activityname.
        $table = new xmldb_table('local_studentmonitoring');

        // Define field studentname to be added to local_studentmonitoring.
        $field = new xmldb_field('studentname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'modname');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field activityname to be added to local_studentmonitoring.
        $field2 = new xmldb_field('activityname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'studentname');
        if (!$dbman->field_exists($table, $field2)) {
            $dbman->add_field($table, $field2);
        }

        // Student monitoring savepoint reached.
        upgrade_plugin_savepoint(true, 2025110401, 'local', 'student_monitoring');
    }

    return true;
}

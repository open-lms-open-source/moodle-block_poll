<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for block_poll.
 *
 * @package    block_poll
 * @copyright  2018 Blackboard Inc.
 * @author     Adam Olley <adam.olley@blackboard.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_poll\privacy;

use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\helper;
use \core_privacy\local\request\writer;
use \core_privacy\local\metadata\collection;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for block_poll implementing null_provider.
 *
 * @copyright  2018 Blackboard Inc.
 * @author     Adam Olley <adam.olley@blackboard.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // The block_comments block stores user provided data.
    \core_privacy\local\metadata\provider,

    // The block_poll block provides data directly to core.
    \core_privacy\local\request\plugin\provider {

    // This trait must be included (to support M3.3).
    use \core_privacy\local\legacy_polyfill;

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function _get_metadata(collection $items) {
        $items->add_database_table(
            'block_poll_response',
            [
                'pollid' => 'privacy:metadata:block_poll_response:pollid',
                'userid' => 'privacy:metadata:block_poll_response:userid',
                'optionid' => 'privacy:metadata:block_poll_response:optionid',
                'submitted' => 'privacy:metadata:block_poll_response:submitted',
            ],
            'privacy:metadata:block_poll_response'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function _get_contexts_for_userid($userid) {
        // Fetch all poll comments.
        $sql = "SELECT c.id
                FROM {context} c
                JOIN {block_poll} b ON b.courseid = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {block_poll_response} r ON r.pollid = b.id
                WHERE r.userid = :userid";

        $params = [
            'contextlevel'  => CONTEXT_COURSE,
            'userid'        => $userid,
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function _export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT b.id as pollid, b.questiontext, o.optiontext, r.submitted, b.courseid
                FROM {context} c
                JOIN {block_poll} b ON b.courseid = c.instanceid AND c.contextlevel = :contextlevel
                JOIN {block_poll_option} o ON o.pollid = b.id
                JOIN {block_poll_response} r ON r.pollid = b.id AND r.optionid = o.id
                WHERE c.id {$contextsql} AND r.userid = :userid
                ORDER BY b.id ASC";
        $params = ['userid' => $user->id, 'contextlevel' => CONTEXT_COURSE] + $contextparams;

        $responses = $DB->get_recordset_sql($sql, $params);
        foreach ($responses as $response) {
            // Users can only make one response per-poll.
            $data = [
                'pollid' => $response->pollid,
                'questiontext' => $response->questiontext,
                'optiontext' => $response->optiontext,
                'submitted' => \core_privacy\local\request\transform::datetime($response->submitted),
            ];
            self::export_poll_data_for_user($data, \context_course::instance($response->courseid), $user);
        }
        $responses->close();
    }

    /**
     * Export the supplied personal data for a single poll, along with any generic data or area files.
     *
     * @param array $data the personal data to export for the poll.
     * @param \context_module $context the context of the poll.
     * @param \stdClass $user the user record
     */
    protected static function export_poll_data_for_user(array $data, \context_course $context, \stdClass $user) {
        // Fetch the generic module data for the poll.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with poll data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $data);
        $subcontext = [get_string('pluginname', 'block_poll')];
        writer::with_context($context)->export_data($subcontext, $contextdata);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function _delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (empty($context)) {
            return;
        }

        if (!$context instanceof \context_course) {
            return;
        }

        $select = "pollid IN (
            SELECT id
            FROM {block_poll}
            WHERE courseid = :courseid
        )";
        $params = ['courseid' => $context->instanceid];
        $DB->delete_records_select('block_poll_response', $select, $params);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function _delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_course) {
                continue;
            }
            $select = "pollid IN (
                SELECT id
                FROM {block_poll}
                WHERE courseid = :courseid
            ) AND userid = :userid";
            $params = [
              'courseid' => $context->instanceid,
              'userid' => $userid,
            ];
            $DB->delete_records_select('block_poll_response', $select, $params);
        }
    }
}

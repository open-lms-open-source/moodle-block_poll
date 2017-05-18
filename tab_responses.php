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
 * Poll block responses tab
 *
 * @package    block_poll
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @copyright  Paul Holden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/locallib.php');

$pid = optional_param('pid', 0, PARAM_INT);
$menu = $DB->get_records_menu('block_poll', ['courseid' => $cid], '', 'id, name');
echo $output->poll_selector($url, $menu, $pid);

if (($poll = $DB->get_record('block_poll', array('id' => $pid)))
    && ($options = $DB->get_records('block_poll_option', array('pollid' => $poll->id)))) {

    $counts = block_poll_get_response_counts($options);
    foreach ($options as $option) {
        $option->responsecount = $counts[$option->id];
    }
    block_poll_sort_results($options, 'block_poll_custom_callback');

    echo $output->box_start();
    echo("<div style=\"text-align:left;\"><strong>$poll->questiontext</strong><ol>");
    foreach ($options as $option) {
        echo("<li>$option->optiontext ($option->responsecount)</li>");
    }
    echo('</ol></div>');
    echo $output->box_end();

    $userfields = user_picture::fields('u');
    $sql = "SELECT DISTINCT $userfields
            FROM {user} u
            JOIN {block_poll_response} r ON r.userid = u.id
            WHERE r.pollid = ?";
    $users = $DB->get_records_sql($sql, [$poll->id]);

    if (!(isset($poll->anonymous) && $poll->anonymous == 1)
        && $responses = $DB->get_records('block_poll_response', array('pollid' => $poll->id), 'submitted ASC')) {
        $responsecount = count($responses);
        $optioncount = count($options);

        $table = new html_table();
        $table->head = array('&nbsp;', get_string('user'), get_string('date'));
        for ($i = 1; $i <= $optioncount; $i++) {
            $table->head[] = $i;
        }
        $table->attributes['class'] = 'generaltable boxalignleft';

        foreach ($responses as $response) {
            if (!isset($users[$response->userid])) {
                continue;
            }
            $table->data[] = array_merge(array($output->user_picture($users[$response->userid], array($cid)),
                                fullname($users[$response->userid]), userdate($response->submitted)),
                                $output->get_response_checks($options, $response->optionid));
        }

        echo html_writer::table($table);
    } else if ((isset($poll->anonymous) && $poll->anonymous == 1 && $poll->locked == 1)
        && $responses = $DB->get_records('block_poll_response', array('pollid' => $poll->id), 'userid ASC')) {
        $responsecount = count($responses);
        // Get min responses required to show users. If unset, set responses to zero to retain default behavior.
        $responsemin = !empty(get_config('block_poll', 'responsecount')) ? get_config('block_poll', 'responsecount') : '0';

        if ($responsecount <= $responsemin || $responsemin == '0') {
            echo html_writer::div(get_string('notenoughresponses',
                    'block_poll', $responsemin + 1) . $responsecount . '.', 'alert alert-error alert-block fade in');
            return;
        }

        $optioncount = count($options);

        $table = new html_table();
        $table->head = array('&nbsp;', get_string('user'));
        $table->attributes['class'] = 'generaltable boxalignleft';

        foreach ($responses as $response) {
            if (!isset($users[$response->userid])) {
                continue;
            }
            $table->data[] = array_merge([
                $output->user_picture($users[$response->userid], [$cid]),
                fullname($users[$response->userid])
            ]);
        }

        echo html_writer::table($table);
    }
}

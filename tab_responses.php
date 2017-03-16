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

$pid = optional_param('pid', 0, PARAM_INTEGER);

require_once(dirname(__FILE__) . '/lib.php');

function poll_custom_callback($a, $b) {
    $counta = $a->responsecount;
    $countb = $b->responsecount;
    return ($counta == $countb ? 0 : ($counta > $countb ? -1 : 1));
}

function block_poll_get_response_checks($options, $selected) {
    foreach ($options as $option) {
        $arr[] = html_writer::checkbox('', '', $option->id == $selected, '',
            array('onclick' => 'this.checked='.($option->id == $selected ? 'true' : 'false')));
    }
    return $arr;
}

$polls = $DB->get_records('block_poll', array('courseid' => $cid));
if ($polls !== false) {
    foreach ($polls as $poll) {
        $menu[$poll->id] = $poll->name;
    }
}

echo $OUTPUT->box_start();
echo html_writer::tag('div', get_string('editpollname', 'block_poll') . ': ', array('class' => 'field_title'));
echo $OUTPUT->single_select($url, 'pid', $menu, $pid);
echo $OUTPUT->box_end();

if (($poll = $DB->get_record('block_poll', array('id' => $pid)))
    && ($options = $DB->get_records('block_poll_option', array('pollid' => $poll->id)))) {
    foreach ($options as $option) {
        $option->responses = $DB->get_records('block_poll_response', array('optionid' => $option->id));
        $option->responsecount = (!$option->responses ? 0 : count($option->responses));
    }
    poll_sort_results($options, 'poll_custom_callback');

    echo $OUTPUT->box_start();
    echo("<div style=\"text-align:left;\"><strong>$poll->questiontext</strong><ol>");
    foreach ($options as $option) {
        echo("<li>$option->optiontext ($option->responsecount)</li>");
    }
    echo('</ol></div>');
    echo $OUTPUT->box_end();

    if (!(isset($poll->anonymous) && $poll->anonymous == 1)
        && $responses = $DB->get_records('block_poll_response', array('pollid' => $poll->id), 'submitted ASC')) {
        $responsecount = count($responses);
        $optioncount = count($options);

        $table = new html_table();
        $table->head = array('&nbsp;', get_string('user'), get_string('date'));
        for ($i = 1; $i <= $optioncount; $i++) {
            $table->head[] = $i;
        }
        $table->tablealign = 'left';
        $table->width = '*';

        foreach ($responses as $response) {
            $user = $DB->get_record('user', array('id' => $response->userid), user_picture::fields());
            if (!$user) {
                continue;
            }
            $table->data[] = array_merge(array($OUTPUT->user_picture($user, array($cid)),
                                fullname($user), userdate($response->submitted)),
                                block_poll_get_response_checks($options, $response->optionid));
        }

        echo html_writer::table($table);
    } else if ((isset($poll->anonymous) && $poll->anonymous == 1 && $poll->locked == 1)
        && $responses = $DB->get_records('block_poll_response', array('pollid' => $poll->id), 'userid ASC')) {
        $responsecount = count($responses);
        // Get min responses required to show users. If unset, set responses to zero to retain default behavior.
        $responsemin = !empty(get_config('block_poll', 'responsecount')) ? get_config('block_poll', 'responsecount') : '0';

        if ($responsecount <= $responsemin || $responsemin == '0') { 
            echo html_writer::div(get_string('notenoughresponses', 'block_poll', $responsemin+1) . $responsecount . '.','alert alert-error alert-block fade in');
            return;
        }

        $optioncount = count($options);

        $table = new html_table();
        $table->head = array('&nbsp;', get_string('user'));
        $table->tablealign = 'left';
        $table->width = '*';

        foreach ($responses as $response) {
            $user = $DB->get_record('user', array('id' => $response->userid), user_picture::fields());
            if (!$user) {
                continue;
            }
            $table->data[] = array_merge(array($OUTPUT->user_picture($user, array($cid)),
                                fullname($user)));
        }

        echo html_writer::table($table);
    }
}

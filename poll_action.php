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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

global $PAGE;

$action = required_param('action', PARAM_ALPHA);
$pid = optional_param('pid', 0, PARAM_INTEGER);
$cid = required_param('id', PARAM_INTEGER);
$srcpage = optional_param('page', '', PARAM_TEXT);
if ($cid == 0) {
    if (!$cid = optional_param('course', 0, PARAM_INT)) {
        $cid = 1;
    }
}
$instanceid = optional_param('instanceid', 0, PARAM_INTEGER);
$sesskey = $USER->sesskey;
$mymoodleref = strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/my/') !== FALSE || strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/admin/stickyblocks.php') !== FALSE;
$stickyblocksref = strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/my/indexsys.php') !== FALSE;

function test_allowed_to_update($cid = 0) {
    global $COURSE;
    $cid = $cid == 0 ? $COURSE->id : $cid;
    $context = get_context_instance(CONTEXT_COURSE, $cid);

    if (has_capability('block/poll:editpoll', $context)) {
        return true;
    } else {
        print_error(get_string('pollwarning', 'block_poll'));
    }
}

if ($stickyblocksref) {
    $url = $CFG->wwwroot.'/admin/stickyblocks.php?pt=my-index';
} else if ($mymoodleref) {
    $url = $CFG->wwwroot.'/my/index.php?';
} else {
    $url = $CFG->wwwroot.'/course/view.php?id='.$cid;
}

$tabs = array('create', 'delete', 'edit');
if (in_array($action, $tabs)) {
    $url = $CFG->wwwroot.'/blocks/poll/tabs.php?';
}

switch ($action) {
    case 'create':
        test_allowed_to_update($cid);
           $poll = new stdClass();
        $poll->id = $pid;
        $poll->name = required_param('name', PARAM_TEXT);
        $poll->courseid = $cid;
        $poll->questiontext = required_param('questiontext', PARAM_TEXT);
        $poll->eligible = required_param('eligible', PARAM_ALPHA);
        $poll->created = time();
        $poll->anonymous = optional_param('anonymous', 0, PARAM_INTEGER);
        $newid = $DB->insert_record('block_poll', $poll, true);
        $optioncount = optional_param('optioncount', 0, PARAM_INTEGER);
        for ($i = 0; $i < $optioncount; $i++) {
            $pollopt = new stdClass();
            $pollopt->id = 0;
            $pollopt->pollid = $newid;
            $pollopt->optiontext = '';
            $DB->insert_record('block_poll_option', $pollopt);
        }
        $url .= "&instanceid=$instanceid&sesskey=$sesskey&blockaction=config&action=editpoll&pid=$newid";
        break;
    case 'edit':
        test_allowed_to_update($cid);
        $poll = $DB->get_record('block_poll', array('id' => $pid));
        $poll->name = required_param('name', PARAM_TEXT);
        $poll->questiontext = required_param('questiontext', PARAM_TEXT);
        $poll->eligible = required_param('eligible', PARAM_ALPHA);
        if ($poll->anonymous == 0) { //only allow one way setting of anonymous
            $poll->anonymous = optional_param('anonymous', 0, PARAM_INTEGER);
        }
        $DB->update_record('block_poll', $poll);
        $options = optional_param('options', array(), PARAM_RAW);
        foreach (array_keys($options) as $option) {
            $pollopt = $DB->get_record('block_poll_option', array('id' => $option));
            $pollopt->optiontext = $options[$option];
            $DB->update_record('block_poll_option', $pollopt);
        }
        $optioncount = optional_param('optioncount', 0, PARAM_INTEGER);
        if (count($options) > $optioncount) {
            $temp = 1;
            foreach ($options as $optid => $optname) {
                if ($temp++ > $optioncount) {
                    break;
                }
                $safe[] = $optid;
            }

            list($insql, $params) = $DB->get_in_or_equal($safe, SQL_PARAMS_NAMED);
            $insql = count($params) > 1 ? "NOT $insql" : "!$insql";
            $params['pid'] = $pid;
            $DB->delete_records_select('block_poll_option', "pollid = :pid AND id $insql", $params);
        }
        for ($i = count($options); $i < $optioncount; $i++) {
            $pollopt = new stdClass();
            $pollopt->id = 0;
            $pollopt->pollid = $pid;
            $pollopt->optiontext = '';
            $DB->insert_record('block_poll_option', $pollopt);
        }
        $url .= "&instanceid=$instanceid&sesskey=$sesskey&blockaction=config&action=editpoll&pid=$pid";
        break;
    case 'delete':
        test_allowed_to_update($cid);
        $step = optional_param('step', 'first', PARAM_TEXT);
        $urlno = $url . "&instanceid=$instanceid&sesskey=$sesskey&blockaction=config&action=managepolls";
        if ($step == 'confirm') {
            $DB->delete_records('block_poll', array('id' => $pid));
            $DB->delete_records('block_poll_option', array('pollid' => $pid));
            $DB->delete_records('block_poll_response', array('pollid' => $pid));
            $url = $urlno;
        } else {
            $poll = $DB->get_record('block_poll', array('id' => $pid));
            $yesparams = array('id' => $cid, 'instanceid' => $instanceid, 'action' => 'delete', 'step' => 'confirm', 'pid' => $pid);
            if ($srcpage != '') {
                $yesparams['page'] = $srcpage;
            }
            $urlyes = new moodle_url('/blocks/poll/poll_action.php', $yesparams);
            notice_yesno(get_string('pollconfirmdelete', 'block_poll', $poll->name), $urlyes, $urlno);
            exit;
        }
        break;
    case 'respond':
        if (!$DB->get_record('block_poll_response', array('pollid' => $pid, 'userid' => $USER->id))) {
            $response = new stdClass();
            $response->id = 0;
            $response->pollid = $pid;
            $response->optionid = required_param('rid', PARAM_INTEGER);
            $response->userid = $USER->id;
            $response->submitted = time();
            $DB->insert_record('block_poll_response', $response);
        }
        break;
}

redirect($url);

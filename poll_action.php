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
 * Poll action controller.
 *
 * @package    block_poll
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @copyright  Paul Holden
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__).'/../../config.php');
require_once('locallib.php');

$action = required_param('action', PARAM_ALPHA);
$pid = optional_param('pid', 0, PARAM_INT);
$cid = required_param('id', PARAM_INT);
$srcpage = optional_param('page', '', PARAM_TEXT);
if ($cid == 0) {
    if (!$cid = optional_param('course', 0, PARAM_INT)) {
        $cid = SITEID;
    }
}
$instanceid = optional_param('instanceid', 0, PARAM_INT);

require_login($cid);

$sesskey = $USER->sesskey;
$mymoodleref = strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/my/') !== false
    || strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/admin/stickyblocks.php') !== false;
$stickyblocksref = strpos($_SERVER["HTTP_REFERER"], $CFG->wwwroot.'/my/indexsys.php') !== false;
$context = context_course::instance($cid);
$pageurl = new moodle_url('/blocks/poll/poll_action.php',
    array('action' => $action, 'id' => $cid, 'pid' => $pid, 'instanceid' => $instanceid));
$PAGE->set_context($context);
$PAGE->set_url($pageurl);

if ($stickyblocksref) {
    $url = new moodle_url('/my/indexsys.php', array('pt' => 'my-index'));
} else if ($mymoodleref) {
    $url = new moodle_url('/my/index.php');
} else {
    $url = new moodle_url('/course/view.php', array('id' => $cid));
}

$tabs = array('create', 'lock', 'edit', 'delete');
if (in_array($action, $tabs)) {
    $url = new moodle_url('/blocks/poll/tabs.php');
}

switch ($action) {
    case 'create':
        block_poll_allowed_to_update($cid);
        $poll = new stdClass();
        $poll->id = $pid;
        $poll->name = required_param('name', PARAM_TEXT);
        $poll->courseid = $cid;
        $poll->questiontext = required_param('questiontext', PARAM_TEXT);
        $poll->eligible = required_param('eligible', PARAM_ALPHA);
        $poll->created = time();
        $poll->anonymous = optional_param('anonymous', 0, PARAM_INT);
        $newid = $DB->insert_record('block_poll', $poll, true);
        $optioncount = optional_param('optioncount', 0, PARAM_INT);
        for ($i = 0; $i < $optioncount; $i++) {
            $pollopt = new stdClass();
            $pollopt->id = 0;
            $pollopt->pollid = $newid;
            $pollopt->optiontext = '';
            $DB->insert_record('block_poll_option', $pollopt);
        }
        $url->params(array(
            'instanceid' => $instanceid,
            'sesskey' => $sesskey,
            'blockaction' => 'config',
            'action' => 'editpoll',
            'pid' => $newid,
        ));
        break;
    case 'lock':
        block_poll_allowed_to_update($cid);
        $step = optional_param('step', 'first', PARAM_TEXT);
        $urlno = clone $url;
        $urlno->params(array(
            'instanceid' => $instanceid,
            'sesskey' => $sesskey,
            'blockaction' => 'config',
            'action' => 'managepolls',
        ));
        if ($step == 'confirm') {
            $sql = 'UPDATE {block_poll}
                    SET locked = 1
                    WHERE id = :pid';
            $DB->execute($sql, array('pid' => $pid));
            $url = $urlno;
        } else {
            $poll = $DB->get_record('block_poll', array('id' => $pid));
            $yesparams = array('id' => $cid, 'instanceid' => $instanceid, 'action' => 'lock', 'step' => 'confirm', 'pid' => $pid);
            $urlyes = new moodle_url('/blocks/poll/poll_action.php', array(
                'id' => $cid,
                'instanceid' => $instanceid,
                'action' => 'lock',
                'step' => 'confirm',
                'pid' => $pid,
            ));
            if ($srcpage != '') {
                $urlyes->param('page', $srcpage);
            }

            $renderer = $PAGE->get_renderer('block_poll');
            echo $renderer->lock_confirmation_page($poll, $urlyes, $urlno);
            exit;
        }
        break;
    case 'edit':
        block_poll_allowed_to_update($cid);
        $poll = $DB->get_record('block_poll', array('id' => $pid));
        $poll->name = required_param('name', PARAM_TEXT);
        $poll->questiontext = required_param('questiontext', PARAM_TEXT);
        $poll->eligible = required_param('eligible', PARAM_ALPHA);
        if ($poll->anonymous == 0) { // Only allow one way setting of anonymous.
            $poll->anonymous = optional_param('anonymous', 0, PARAM_INTEGER);
        }
        $DB->update_record('block_poll', $poll);
        $options = optional_param_array('options', array(), PARAM_RAW);
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
        $url->params(array(
            'instanceid' => $instanceid,
            'sesskey' => $sesskey,
            'blockaction' => 'config',
            'action' => 'editpoll',
            'pid' => $pid,
        ));
        break;
    case 'delete':
        block_poll_allowed_to_update($cid);
        $step = optional_param('step', 'first', PARAM_TEXT);
        $urlno = clone $url;
        $urlno->params(array(
            'instanceid' => $instanceid,
            'sesskey' => $sesskey,
            'blockaction' => 'config',
            'action' => 'managepolls',
        ));
        if ($step == 'confirm') {
            $DB->delete_records('block_poll', array('id' => $pid));
            $DB->delete_records('block_poll_option', array('pollid' => $pid));
            $DB->delete_records('block_poll_response', array('pollid' => $pid));
            $url = $urlno;
        } else {
            $poll = $DB->get_record('block_poll', array('id' => $pid));
            $yesparams = array('id' => $cid, 'instanceid' => $instanceid, 'action' => 'delete', 'step' => 'confirm', 'pid' => $pid);
            $urlyes = new moodle_url('/blocks/poll/poll_action.php', array(
                'id' => $cid,
                'instanceid' => $instanceid,
                'action' => 'delete',
                'step' => 'confirm',
                'pid' => $pid,
            ));
            if ($srcpage != '') {
                $urlyes->param('page', $srcpage);
            }

            $renderer = $PAGE->get_renderer('block_poll');
            echo $renderer->delete_confirmation_page($poll, $urlyes, $urlno);
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

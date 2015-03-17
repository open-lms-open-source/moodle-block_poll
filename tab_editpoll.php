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

defined('MOODLE_INTERNAL') || exit;

$id = optional_param('id', 0, PARAM_INT);
$pid = optional_param('pid', 0, PARAM_INT);

$polls = $DB->get_records('block_poll', array('courseid' => $cid));
$menu = array();
if ($polls !== false) {
    foreach ($polls as $poll) {
        $menu[$poll->id] = $poll->name;
    }
}
// TODO: Renderify.
echo $OUTPUT->box_start();
echo html_writer::start_tag('form', array('action' => '#', 'method' => 'get', 'id' => 'pollselect_form'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'cid', 'value' => $cid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'instanceid', 'value' => $instanceid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'editpoll'));
echo get_string('editpollname', 'block_poll').': ';
echo html_writer::select($menu, 'pid', $pid, array('' => 'choosedots'), array('id' => 'pid', 'class' => 'autosubmit'));
echo html_writer::end_tag('form');
$PAGE->requires->yui_module('moodle-core-formautosubmit',
    'M.core.init_formautosubmit',
    array(array('selectid' => 'pid', 'nothing' => false)));
echo $OUTPUT->box_end();

$poll = $DB->get_record('block_poll', array('id' => $pid));
$polloptions = array();
if ($pid > 0) {
    $polloptions = $DB->get_records('block_poll_option', array('pollid' => $pid));
}
$polloptioncount = count($polloptions);

echo html_writer::start_tag('form', array('method' => "post", 'action' => $CFG->wwwroot.'/blocks/poll/poll_action.php'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pid', 'value' => $pid));
$action = $pid == 0 ? 'create' : 'edit';
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => $action));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'instanceid', 'value' => $instanceid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id', 'value' => $id));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => $USER->sesskey));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'blockaction', 'value' => 'config'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'course', 'value' => $COURSE->id));

$eligible = array('all' => get_string('all'), 'students' => get_string('students'), 'teachers' => get_string('teachers'));
for ($i = 1; $i <= 10; $options[$i++] = ($i - 1)) {
}

$table = new html_table();
$table->head = array(get_string('config_param', 'block_poll'), get_string('config_value', 'block_poll'));
$table->tablealign = 'left';
$table->width = '*';

$stranonresp = get_string('editanonymousresponses', 'block_poll');
$anoncheck = isset($poll->anonymous) && $poll->anonymous == 1 ? 'checked="checked" disabled="disabled"' : '';

$table->data[] = array(get_string('editpollname', 'block_poll'),
    '<input type="text" name="name" value="' . ((!isset($poll) || !$poll) ? '' : $poll->name) . '" />');
$table->data[] = array(get_string('editpollquestion', 'block_poll'),
    '<input type="text" name="questiontext" value="' . (!$poll ? '' : $poll->questiontext) . '" />');
$table->data[] = array($stranonresp, '<input type="checkbox" name="anonymous" alt="'.$stranonresp.'" value="1" '.$anoncheck.' />');
$selected = isset($poll->eligible) ? $poll->eligible : 'all';
$table->data[] = array(get_string('editpolleligible', 'block_poll'), html_writer::select($eligible, 'eligible', $selected));
$selected = $pid > 0 ? $polloptioncount : 5;
$table->data[] = array(get_string('editpolloptions', 'block_poll'), html_writer::select($options, 'optioncount', $selected));

$optioncount = 0;
if (is_array($polloptions)) {
    foreach ($polloptions as $option) {
        $optioncount++;
        $table->data[] = array(get_string('option', 'block_poll') . " $optioncount",
            "<input type=\"text\" name=\"options[$option->id]\" value=\"$option->optiontext\" />");
    }
}
for ($i = $optioncount + 1; $i <= $polloptioncount; $i++) {
    $table->data[] = array(get_string('option', 'block_poll') . " $i", '<input type="text" name="newoptions[]" />');
}

$table->data[] = array('&nbsp;', '<input type="submit" value="' . get_string('savechanges') . '" />');

echo html_writer::table($table);
echo html_writer::end_tag('form');

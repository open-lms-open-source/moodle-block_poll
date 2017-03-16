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

function print_action($action, $url) {
    global $OUTPUT;
    return html_writer::link($url, html_writer::tag('img', '', array('src' => 'pix/' . $action . '.svg', 'title' => $action, 'alt' => $action)));
}

$edit = get_string('edit');
$lock = get_string('lock', 'block_poll');
$delete = get_string('delete');
$view = get_string('view');
$preview = get_string('preview');

$polls = $DB->get_records('block_poll', array('courseid' => $COURSE->id));

// TODO: Use html_table.
$table = new html_table();
$table->head = array(get_string('editpollname', 'block_poll'),
             get_string('editpolloptions', 'block_poll'),
             get_string('responses', 'block_poll'),
             get_string('action'));
$table->align = array('left', 'right', 'right', 'left');
$table->tablealign = 'left';
$table->width = '*';

if ($polls !== false) {
    foreach ($polls as $poll) {
        $options = $DB->get_records('block_poll_option', array('pollid' => $poll->id));
        $responses = $DB->get_records('block_poll_response', array('pollid' => $poll->id));

        $urlpreview = clone $url;
        $urlpreview->params(array('action' => 'responses', 'pid' => $poll->id));
        $urledit = clone $url;
        $urledit->params(array('action' => 'editpoll', 'pid' => $poll->id));
        $urldelete = new moodle_url('/blocks/poll/poll_action.php',
            array('action' => 'delete', 'id' => $cid, 'pid' => $poll->id, 'instanceid' => $instanceid));
        $urllock = new moodle_url('/blocks/poll/poll_action.php',
            array('action' => 'lock', 'id' => $cid, 'pid' => $poll->id, 'instanceid' => $instanceid));

        $action = $poll->locked == 0 ? 
                  print_action('preview', $urlpreview) .
                  print_action('lock', $urllock) .
                  print_action('edit', $urledit) .
                  print_action('delete', $urldelete) : 
                  print_action('preview', $urlpreview) .
                  print_action('delete', $urldelete);
        $table->data[] = array($poll->name, (!$options ? '0' : count($options)), (!$responses ? '0' : count($responses)), $action);
    }
}

echo html_writer::table($table);

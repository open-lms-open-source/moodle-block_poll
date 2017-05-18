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
 * Form for editing the poll block.
 *
 * @package    block_poll
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Class for defining the poll blocks edit form.
 *
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_poll_edit_form extends block_edit_form {

    /**
     * Define the custom fields to display when editing a poll block.
     *
     * @param moodleform $mform
     */
    protected function specific_definition($mform) {
        global $COURSE, $DB;
        // Fields for editing poll block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_customtitle', get_string('configtitle', 'block_html'));
        $mform->setType('config_customtitle', PARAM_TEXT);

        if ($polls = $DB->get_records('block_poll', array('courseid' => $COURSE->id), '', 'id, name')) {
            $list = array(0 => get_string('choose', 'block_poll'));
            foreach ($polls as $poll) {
                $list[$poll->id] = $poll->name;
            }
            $mform->addElement('select', 'config_pollid', get_string('editpollname', 'block_poll'), $list);
        } else {
            $mform->addElement('static', 'nopolls', get_string('editpollname', 'block_poll'),
                get_string('nopollsavailable', 'block_poll'));
        }

        $mform->setType('config_maxwidth', PARAM_INT);
        $mform->addElement('text', 'config_maxwidth', get_string('editmaxbarwidth', 'block_poll'));

        $tabs = array('editpoll', 'managepolls', 'responses');
        foreach ($tabs as $tab) {
            $params = array('action' => $tab, 'cid' => $COURSE->id, 'instanceid' => $this->block->instance->id);
            $link = html_writer::link(new moodle_url('/blocks/poll/tabs.php', $params), get_string("tab$tab", 'block_poll'));
            $mform->addElement('static', "linki_$tab", '', $link);
        }
    }
}

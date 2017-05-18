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
 * Class containing data for poll block.
 *
 * @package    block_poll
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_poll\output;
defined('MOODLE_INTERNAL') || die();

use renderable;
use renderer_base;
use templatable;

/**
 * Class containing data for managing polls.
 *
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class managepolls implements renderable, templatable {

    /** @var int $courseid The course we're managing. */
    protected $courseid;

    /** @var int $instanceid The block instance id */
    protected $instanceid;

    /** @var array $polls The list of polls in this course. */
    protected $polls = [];

    /** @var \moodle_url $url URL of the current page */
    protected $url;

    /**
     * The managepolls constructor.
     *
     * @param int $courseid a course id.
     * @param int $instanceid the block instance id
     * @param \moodle_url $url current page url
     * @param array $polls the list of polls for this course
     */
    public function __construct($courseid, $instanceid, \moodle_url $url, $polls = []) {
        $this->courseid = $courseid;
        $this->instanceid = $instanceid;
        $this->polls = $polls;
        $this->url = $url;
        $this->url->remove_params(['action', 'id']);
        $this->url->param('instanceid', $instanceid);
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param \renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;
        $rows = [];

        foreach ($this->polls as $poll) {
            $options = $DB->get_records('block_poll_option', array('pollid' => $poll->id));
            $responses = $DB->get_records('block_poll_response', array('pollid' => $poll->id));

            $urlpreview = clone $this->url;
            $urlpreview->params(array('action' => 'responses', 'pid' => $poll->id));
            $urledit = clone $this->url;
            $urledit->params(array('action' => 'editpoll', 'pid' => $poll->id));
            $urldelete = new \moodle_url('/blocks/poll/poll_action.php',
                array('action' => 'delete', 'id' => $this->courseid, 'pid' => $poll->id, 'instanceid' => $this->instanceid));
            $urllock = new \moodle_url('/blocks/poll/poll_action.php',
                array('action' => 'lock', 'id' => $this->courseid, 'pid' => $poll->id, 'instanceid' => $this->instanceid));

            $rows[] = [
                'id' => $poll->id,
                'title' => $poll->name,
                'optioncount' => (!$options ? '0' : count($options)),
                'responsecount' => (!$responses ? '0' : count($responses)),
                'urlpreview' => $urlpreview->out(false),
                'urllock' => $urllock->out(false),
                'urledit' => $urledit->out(false),
                'urldelete' => $urldelete->out(false),
            ];
        }

        return [
            'baseurl' => $this->url->out(false),
            'courseid' => $this->courseid,
            'instanceid' => $this->instanceid,
            'rows' => $rows,
        ];
    }
}

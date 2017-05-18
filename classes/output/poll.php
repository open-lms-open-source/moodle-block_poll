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

use context;
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
class poll implements renderable {

    /**
     * @var bool Can the user edit this poll?
     */
    public $canedit = false;

    /**
     * @var int The course id.
     */
    public $courseid;

    /**
     * @var bool Is the user eligible to vote in this poll right now.
     */
    public $eligible;

    /**
     * @var int The poll id.
     */
    public $id;

    /**
     * @var int The maximum width (in pixels) of the voting bars.
     */
    public $maxwidth;

    /**
     * @var string The poll question.
     */
    public $questiontext;

    /**
     * @var array List of voting options.
     */
    public $options;

    /**
     * @var \stdClass The current users response to the poll.
     */
    public $response;

    /**
     * @var array List of the counts each option got.
     */
    public $results = [];

    /**
     * Poll constructor.
     *
     * @param \context $context
     * @param int $courseid
     * @param \stdClass $user A user record (typically the current user).
     * @param \stdClass $poll The poll record.
     * @param array $options List of options records.
     * @param \stdClass|bool $response The users response to the poll (or false).
     * @param int $maxwidth
     */
    public function __construct($context, $courseid, $user, $poll, $options, $response, $maxwidth) {
        $this->courseid = $courseid;
        $this->questiontext = $poll->questiontext;
        $this->maxwidth = $maxwidth;
        $this->options = $options;
        $this->id = $poll->id;
        $this->response = $response;

        $this->canedit = has_capability('block/poll:editpoll', $context);

        $this->load_results();
        $this->eligible = $this->user_eligible($context, $user, $poll);
    }

    /**
     * Load the list of results for the current poll.
     *
     * @param bool $sort
     */
    protected function load_results($sort = true) {
        $counts = block_poll_get_response_counts($this->options);
        foreach ($counts as $optionid => $count) {
            $text = $this->options[$optionid]->optiontext;
            $results[$text] = $count;
        }
        if ($sort) {
            block_poll_sort_results($results);
        }
        $this->results = $results;
    }

    /**
     * Detemine if the user is elibible to vote in this poll right now.
     *
     * @param \context $context
     * @param \stdClass $user
     * @param \stdClass $poll
     * @return bool
     */
    protected function user_eligible($context, $user, $poll) {
        $parents = $context->get_parent_context_ids();
        $parentctx = context::instance_by_id($parents[0]);

        $switched = false;
        if ($poll->eligible == 'students') {
            $switched = is_role_switched($this->courseid);
            if ($switched && isset($user->access['rsw'][$parentctx->path])) {
                $switched = !role_context_capabilities($user->access['rsw'][$parentctx->path], $context, 'block/poll:editpoll');
            }
        }

        if ($poll->locked != 0) {
            // No-one gets to vote in a locked poll.
            return false;
        }

        $studentsonly = $poll->eligible == 'students' && !$this->canedit;
        $teachersonly = $poll->eligible == 'teachers' && $this->canedit;

        // A user is eligible to vote if:
        // - poll is to 'all'.
        // - poll is set to students and the user cant edit the poll.
        // - poll is set to teachers and the user *can* edit the poll.
        // - poll is set to students and the user has switched to a student role.
        return $poll->eligible == 'all' || $studentsonly || $switched || $teachersonly;
    }
}
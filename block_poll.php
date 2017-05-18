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
 * Poll block
 *
 * @package    block_poll
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/blocks/poll/locallib.php");

/**
 * Poll block
 *
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_poll extends block_base {

    /**
     * Whether the block has configuration (it does)
     *
     * @return  boolean     We do have configuration
     */
    public function has_config() {
        return true;
    }

    /**
     * Init.
     */
    public function init() {
        $this->title = get_string('formaltitle', 'block_poll');
    }

    /**
     * Specify that we have instance specific configuration.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Set the title of our block if one is configured.
     */
    public function specialization() {
        if (!empty($this->config) && !empty($this->config->customtitle)) {
            $this->title = $this->config->customtitle;
        }
    }

    /**
     * Returns the contents.
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        global $COURSE, $DB, $USER;
        if ($this->content !== null) {
            return $this->content;
        }

        if (!isset($this->config->pollid) || !is_numeric($this->config->pollid)) {
            $this->content = new stdClass();
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        $renderer = $this->page->get_renderer('block_poll');

        $poll = $DB->get_record('block_poll', array('id' => $this->config->pollid));
        $options = $DB->get_records('block_poll_option', array('pollid' => $poll->id));
        $response = $DB->get_record('block_poll_response', array('pollid' => $poll->id, 'userid' => $USER->id));
        $maxwidth = !empty($this->config->maxwidth) ? $this->config->maxwidth : 150;

        $this->content = new stdClass();
        $renderable = new block_poll\output\poll($this->context, $COURSE->id, $USER, $poll, $options, $response, $maxwidth);
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = $renderer->footertext($poll, $this->instance->id, $renderable->canedit);

        return $this->content;
    }

}

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
 * Poll block renderer
 *
 * @package    block_poll
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_poll\output;
defined('MOODLE_INTERNAL') || die;

use html_writer;
use moodle_url;
use plugin_renderer_base;

/**
 * Poll block renderer
 *
 * @copyright  2017 Adam Olley <adam.olley@blackboard.com>
 * @copyright  2017 Blackboard Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Return the managepolls tab content.
     *
     * @param managepolls $tab The managepolls tab renderable
     * @return string HTML string
     */
    public function render_managepolls(managepolls $tab) {
        return $this->render_from_template('block_poll/managepolls', $tab->export_for_template($this));
    }

    /**
     * Displays the poll delete confirmation page.
     *
     * @param stdClass $poll the poll record
     * @param string $yes
     * @param string $no
     * @return string
     */
    public function delete_confirmation_page($poll, $yes, $no) {
        $html = $this->output->header();
        $message = get_string('pollconfirmdelete', 'block_poll', $poll->name);
        $html .= $this->output->confirm($message, $yes, $no);
        $html .= $this->output->footer();
        return $html;
    }

    /**
     * Displays the poll lock confirmation page.
     *
     * @param stdClass $poll the poll record
     * @param string $yes
     * @param string $no
     * @return string
     */
    public function lock_confirmation_page($poll, $yes, $no) {
        $html = $this->output->header();
        $message = get_string('pollconfirmlock', 'block_poll', $poll->name);
        $html .= $this->output->confirm($message, $yes, $no);
        $html .= $this->output->footer();
        return $html;
    }

    /**
     * Display the poll block.
     *
     * @param poll $poll
     * @return string
     */
    public function render_poll(poll $poll) {
        $html = '<table cellspacing="2" cellpadding="2">';
        $html .= '<tr><th>' . $poll->questiontext . '</th></tr>';

        $func = 'poll_results';
        if (!$poll->response && $poll->eligible) {
            $func = 'poll_options';
        }
        $html .= $this->$func($poll);

        $html .= '</table>';

        return $html;
    }

    /**
     * Render the content to display beneath the poll options.
     *
     * @param \stdClass $poll The poll record.
     * @param int $instanceid The block instance id.
     * @param bool $canedit The user has poll editing capabilities.
     * @return string
     */
    public function footertext($poll, $instanceid, $canedit) {
        $html = '';

        if ($canedit) {
            $html .= $this->results_link($instanceid, $poll);
        }

        $class = 'error';
        $strid = 'notanonymous';
        if (!empty($poll->anonymous)) {
            $class = 'success';
            $strid = 'useranonymous';
        }
        $html .= \html_writer::div(get_string($strid, 'block_poll'), "center alert alert-block fade in alert-{$class}");
        return $html;
    }

    /**
     * Link to view the poll responses.
     *
     * @param int $instanceid The block instance id.
     * @param stdClass $poll The poll record.
     * @return string
     */
    public function results_link($instanceid, $poll) {
        $url = new moodle_url('/blocks/poll/tabs.php', ['action' => 'responses', 'pid' => $poll->id, 'instanceid' => $instanceid]);
        $html = html_writer::empty_tag('hr');
        $html .= html_writer::link($url, get_string('responses', 'block_poll'));
        return $html;
    }

    /**
     * Display the list of poll options so the user can cast their vote.
     *
     * @param poll $poll The poll renderable.
     * @return string
     */
    public function poll_options(poll $poll) {
        $html = '';

        $url = new moodle_url('/blocks/poll/poll_action.php');
        $html = html_writer::start_tag('form', ['method' => 'get', 'action' => $url]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'respond']);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'pid', 'value' => $poll->id]);
        $html .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $poll->courseid]);

        foreach ($poll->options as $option) {
            $input = html_writer::empty_tag('input',
                ['type' => 'radio', 'id' => "r_{$option->id}", 'name' => 'rid', 'value' => $option->id]);
            $label = html_writer::label($option->optiontext, "r_{$option->id}");
            $html .= "<tr><td>{$input}{$label}</td></tr>";
        }
        $html .= '<tr><td>';
        $html .= html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('submit', 'block_poll')]);
        $html .= '</td></tr>';
        $html .= html_writer::end_tag('form');

        return $html;
    }

    /**
     * Display the poll results. For after the user has cast their vote, or is ineligible to vote themselves.
     *
     * @param poll $poll
     * @return string
     */
    public function poll_results(poll $poll) {
        $html = '';
        $highest = 1;
        foreach ($poll->results as $option => $count) {
            if ($count > $highest) {
                $highest = $count;
            }
        }

        foreach ($poll->results as $option => $count) {
            $img = ((isset($img) && $img == 0) ? 1 : 0);
            $imgwidth = round($count / $highest * $poll->maxwidth);
            $html .= "<tr><td>$option ($count)<br />" . $this->poll_graphbar($img, $imgwidth) . '</td></tr>';
        }
        return $html;
    }

    /**
     * Display a bar representing the ratio of votes for this option.
     *
     * @param string $img
     * @param string $width
     * @return string
     */
    public function poll_graphbar($img = '0', $width = '100') {
        $html = $this->pix_icon("graph{$img}", '', 'block_poll', array('style' => "width: {$width}px; height: 15px;"));
        $html .= html_writer::empty_tag('br');
        return $html;
    }

    /**
     * Get a list of checkmarks indicating which option a user chose.
     *
     * @param array $options List of options
     * @param int $selected option id the user selected
     * @return array
     */
    public function get_response_checks($options, $selected) {
        $arr = [];
        foreach ($options as $option) {
            $arr[] = html_writer::checkbox('', '', $option->id == $selected, '',
                ['onclick' => 'this.checked='.($option->id == $selected ? 'true' : 'false')]);
        }
        return $arr;
    }

    /**
     * Drop down selector of polls to choose from on the management tabs.
     *
     * @param moodle_url $url
     * @param array $menu List of polls.
     * @param int $pid The currently selected poll (if any).
     * @return string
     */
    public function poll_selector($url, $menu, $pid) {
        $html = $this->box_start();
        $html .= html_writer::tag('div', get_string('editpollname', 'block_poll') . ': ', ['class' => 'field_title']);
        $html .= $this->single_select($url, 'pid', $menu, $pid);
        $html .= $this->box_end();
        return $html;
    }
}

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

require_once("$CFG->dirroot/blocks/poll/lib.php");

class block_poll extends block_base {

    public $poll, $options;

    /**
     * Whether the block has configuration (it does)
     *
     * @return  boolean     We do have configuration
     */
    public function has_config() {
        return true;
    }

    public function init() {
        $this->title = get_string('formaltitle', 'block_poll');
        $this->version = 2015031700;
    }

    public function instance_allow_config() {
        return true;
    }

    public function specialization() {
    if (!empty($this->config) && !empty($this->config->customtitle)) {
            $this->title = $this->config->customtitle;
    } else {
            $this->title = get_string('formaltitle', 'block_poll');
        }
    }

    public function poll_can_edit() {
        return has_capability('block/poll:editpoll', $this->context);
    }

    public function poll_user_eligible() {
        global $COURSE, $USER;

        $parents = $this->context->get_parent_context_ids();
        $parentctx = context::instance_by_id($parents[0]);

        $switched = false;
        if ($this->poll->eligible == 'students') {
            $switched = is_role_switched($COURSE->id);
            if (isset($USER->access['rsw'][$parentctx->path])) {
                $switched = $switched
                    && !role_context_capabilities($USER->access['rsw'][$parentctx->path], $this->context, 'block/poll:editpoll');
            } else {
                $switched = false;
            }
        }
        // TODO: Proper roles & capabilities.
        if ($this->poll->locked == 0) {
            return ($this->poll->eligible == 'all') ||
                (($this->poll->eligible == 'students') && !$this->poll_can_edit()) ||
                ($switched) ||
                (($this->poll->eligible == 'teachers') && $this->poll_can_edit());
        } else { 
           return false; 
        }
    }

    public function poll_results_link() {
        $url = new moodle_url('/blocks/poll/tabs.php',
            array('action' => 'responses', 'pid' => $this->poll->id, 'instanceid' => $this->instance->id));
        $html = html_writer::empty_tag('hr');
        $html .= html_writer::link($url, get_string('responses', 'block_poll'));
        return $html;
    }

    public function poll_print_options() {
        global $CFG, $COURSE;
        // TODO: Renderer/html_writer-ify.
        $this->content->text .= '<form method="get" action="' . $CFG->wwwroot . '/blocks/poll/poll_action.php">
                     <input type="hidden" name="action" value="respond" />
                     <input type="hidden" name="pid" value="' . $this->poll->id . '" />
                     <input type="hidden" name="id" value="' . $COURSE->id . '" />';
        foreach ($this->options as $option) {
            $this->content->text .= "<tr><td><input type=\"radio\" id=\"r_$option->id\" name=\"rid\" value=\"$option->id\" />
                         <label for=\"r_$option->id\">$option->optiontext</label></td></tr>";
        }
        $this->content->text .= '<tr><td><input type="submit" value="' . get_string('submit', 'block_poll') . '" />
            </td></tr></form>';
    }

    public function poll_get_results(&$results, $sort = true) {
        global $DB;
        foreach ($this->options as $option) {
            $responses = $DB->get_records('block_poll_response', array('optionid' => $option->id));
            $results[$option->optiontext] = (!$responses ? '0' : count($responses));
        }
        if ($sort) {
            poll_sort_results($results);
        }
    }

    public function poll_print_results() {
        $this->poll_get_results($results);
        $highest = 1;
        foreach ($results as $option => $count) {
            if ($count > $highest) {
                $highest = $count;
            }
        }
        $maxwidth = !empty($this->config->maxwidth) ? $this->config->maxwidth : 150;

        foreach ($results as $option => $count) {
            $img = ((isset($img) && $img == 0) ? 1 : 0);
            $imgwidth = round($count / $highest * $maxwidth);
            $this->content->text .= "<tr><td>$option ($count)<br />" . poll_get_graphbar($img, $imgwidth) . '</td></tr>';
        }
    }

    public function get_content() {
        global $DB, $USER;
        if ($this->content !== null) {
            return $this->content;
        }

        if (!isset($this->config->pollid) || !is_numeric($this->config->pollid)) {
            $this->content = new stdClass();
            $this->content->text = '';
            $this->content->footer = '';
            return $this->content;
        }

        $this->poll = $DB->get_record('block_poll', array('id' => $this->config->pollid));
        $footertext = $this->poll->anonymous == 1 ? (html_writer::div(get_string('useranonymous', 'block_poll'), 'center alert alert-success alert-block fade in')) : (html_writer::div(get_string('notanonymous', 'block_poll'), 'center alert alert-error alert-block fade in'));

        $this->options = $DB->get_records('block_poll_option', array('pollid' => $this->poll->id));

        // TODO: html_table.
        $this->content = new stdClass();
        $this->content->text = '<table cellspacing="2" cellpadding="2">';
        $this->content->text .= '<tr><th>' . $this->poll->questiontext . '</th></tr>';

        $response = $DB->get_record('block_poll_response', array('pollid' => $this->poll->id, 'userid' => $USER->id));
        $func = 'poll_print_' . (!$response && $this->poll_user_eligible() ? 'options' : 'results');
        $this->$func();

        $this->content->text .= '</table>';

        $this->content->footer = '';
        $this->content->footer .= ($this->poll_can_edit() ? $this->poll_results_link() : '');
        $this->content->footer .= $footertext;

        return $this->content;
    }

}

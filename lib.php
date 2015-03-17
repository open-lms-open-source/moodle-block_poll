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

function poll_sort_callback($a, $b) {
    return ($a == $b ? 0 : ($a > $b ? -1 : 1));
}

function poll_sort_results(&$options, $callback = 'poll_sort_callback') {
    return uasort($options, $callback);
}

function poll_get_graphbar($img = '0', $width = '100') {
    global $CFG, $OUTPUT;
    $html = $OUTPUT->pix_icon("graph$img", '', 'block_poll', array('style' => "width: {$width}px; height: 15px;"));
    $html .= html_writer::empty_tag('br');
    return $html;
}

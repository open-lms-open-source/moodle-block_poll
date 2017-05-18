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
 * Settings for the poll block plugin.
 *
 * @package   block_poll
 * @copyright 2016, Robert Russo
 * @copyright 2016, Louisiana State University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    require_once($CFG->libdir . '/adminlib.php');

    // Default minumum user responses before showing users.
    $settings->add( new admin_setting_configtext(
        'block_poll/responsecount',
        new lang_string('minresponses', 'block_poll'),
        new lang_string('minresponseshelp', 'block_poll'),
        '10',
        PARAM_INT
    ));
}

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
 * @package   block_poll
 * @copyright 2016, Robert Russo
 * @copyright 2016, Louisiana State University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (is_siteadmin()) {

    require_once(dirname(__FILE__) . '/../../config.php');
    require_once($CFG->libdir . '/adminlib.php');

    // Create the new settings page
    $settings = new admin_settingpage('block_poll', get_string('formaltitle', 'block_poll'));

    // default_minumum user responses before showing users
    $settings->add( new admin_setting_configtext(
        'block_poll/responsecount',
        get_string('minresponses', 'block_poll'),
        get_string('minresponseshelp', 'block_poll'),
        '10',
        PARAM_INT
    ));
}

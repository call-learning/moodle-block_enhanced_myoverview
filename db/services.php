<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Block enhanced_myoverview access
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
$functions = [
    'block_enhanced_myoverview_get_enrolled_courses_by_timeline_classification' => [
        'classname' => 'block_enhanced_myoverview\external\get_enrolled_courses_by_timeline',
        'methodname' => 'get_enrolled_courses_by_timeline_classification',
        'description' => 'List of enrolled courses for the given timeline classification (past, inprogress, or future). Filter'
            . ' course by role on the course',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ]
];

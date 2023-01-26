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
 * External course API
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_enhanced_myoverview\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/course/externallib.php");


use block_enhanced_myoverview\filter_courses;
use context_course;
use context_helper;
use context_user;
use core_course\external\course_summary_exporter;
use core_course_external;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use invalid_parameter_exception;

/**
 * Class allowing to get courses by timeline using external API
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_enrolled_courses_by_timeline extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_courses_by_timeline_classification_parameters() {
        return new external_function_parameters(
            array(
                'classification' => new external_value(PARAM_ALPHA, 'future, inprogress, or past'),
                'sort' => new external_value(PARAM_TEXT, 'Sort string', VALUE_DEFAULT, null),
                'customfieldname' => new external_value(PARAM_ALPHANUMEXT, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'customfieldvalue' => new external_value(PARAM_RAW, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'additionalfilter' => new external_value(PARAM_ALPHA, 'iteach, ...'), // Further classification.
                'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
            )
        );
    }

    /**
     * Get courses matching the given timeline classification.
     *
     * NOTE: The offset applies to the unfiltered full set of courses before the classification
     * filtering is done.
     * E.g.
     * If the user is enrolled in 5 courses:
     * c1, c2, c3, c4, and c5
     * And c4 and c5 are 'future' courses
     *
     * If a request comes in for future courses with an offset of 1 it will mean that
     * c1 is skipped (because the offset applies *before* the classification filtering)
     * and c4 and c5 will be return.
     *
     * @param string $classification past, inprogress, or future
     * @param string $sort SQL sort string for results
     * @param string $customfieldname
     * @param string $customfieldvalue
     * @param string $additionalfilter
     * @param int $limit Result set limit
     * @param int $offset Offset the full course set before timeline classification is applied
     * @return array list of courses and warnings
     */
    public static function get_enrolled_courses_by_timeline_classification(
        string $classification,
        string $sort,
        string $customfieldname,
        string $customfieldvalue,
        string $additionalfilter,
        int $limit = 0,
        int $offset = 0
    ) {
        global $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(
            self::get_enrolled_courses_by_timeline_classification_parameters(),
            array(
                'classification' => $classification,
                'limit' => $limit,
                'offset' => $offset,
                'sort' => $sort,
                'customfieldvalue' => $customfieldvalue,
                'additionalfilter' => $additionalfilter
            )
        );
        self::validate_context(context_user::instance($USER->id));

        $classification = $params['classification'];
        $limit = $params['limit'];
        $offset = $params['offset'];
        $sort = $params['sort'];
        $customfieldvalue = $params['customfieldvalue'];

        if (!in_array($classification, [
            COURSE_TIMELINE_ALLINCLUDINGHIDDEN,
            COURSE_TIMELINE_ALL,
            COURSE_TIMELINE_PAST,
            COURSE_TIMELINE_INPROGRESS,
            COURSE_TIMELINE_FUTURE,
            COURSE_FAVOURITES,
            COURSE_TIMELINE_HIDDEN,
            COURSE_CUSTOMFIELD
        ])) {
            throw new invalid_parameter_exception('Invalid classification');
        }
        list($filteredcourses, $processedcount, $favouritecourseids) = filter_courses::filter_my_courses(
            $classification,
            $limit,
            $offset,
            $sort,
            $customfieldname,
            $customfieldvalue,
            $additionalfilter);
        $renderer = $PAGE->get_renderer('core');
        $formattedcourses = array_map(function($course) use ($renderer, $favouritecourseids) {
            context_helper::preload_from_record($course);
            $context = context_course::instance($course->id);
            $isfavourite = false;
            if (in_array($course->id, $favouritecourseids)) {
                $isfavourite = true;
            }
            $exporter = new course_summary_exporter($course, ['context' => $context, 'isfavourite' => $isfavourite]);
            return $exporter->export($renderer);
        }, $filteredcourses);

        return [
            'courses' => $formattedcourses,
            'nextoffset' => $offset + $processedcount
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function get_enrolled_courses_by_timeline_classification_returns(): external_single_structure {
        return core_course_external::get_enrolled_courses_by_timeline_classification_returns();
    }
}

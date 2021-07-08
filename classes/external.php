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

namespace block_enhanced_myoverview;
defined('MOODLE_INTERNAL') || die;

use context_course;
use context_helper;
use context_user;
use core_course\external\course_summary_exporter;
use core_course_external;
use external_api;
use external_description;
use external_function_parameters;
use external_value;
use invalid_parameter_exception;

global $CFG;

require_once("$CFG->libdir/externallib.php");
require_once("$CFG->dirroot/course/externallib.php");

/**
 * Course external functions
 *
 * @package     block_enhanced_myoverview
 * @category   external
 * @copyright  2011 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.2
 */
class external extends external_api {
    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_courses_by_timeline_classification_parameters() {
        return new external_function_parameters(
            array(
                'classification' => new external_value(PARAM_ALPHA, 'future, inprogress, or past'),
                'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
                'sort' => new external_value(PARAM_TEXT, 'Sort string', VALUE_DEFAULT, null),
                'customfieldname' => new external_value(PARAM_ALPHANUMEXT, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'customfieldvalue' => new external_value(PARAM_RAW, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'additionalfilter' => new external_value(PARAM_ALPHA, 'iteach, ...'), // Further classification.
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
     * @param int $limit Result set limit
     * @param int $offset Offset the full course set before timeline classification is applied
     * @param string $sort SQL sort string for results
     * @param string $customfieldname
     * @param string $customfieldvalue
     * @param string $additionalfilter
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     */
    public static function get_enrolled_courses_by_timeline_classification(
        string $classification,
        int $limit = 0,
        int $offset = 0,
        string $sort = null,
        string $customfieldname = null,
        string $customfieldvalue = null,
        string $additionalfilter = null
    ) {
        global $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $params = self::validate_parameters(static::get_enrolled_courses_by_timeline_classification_parameters(),
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

        switch ($classification) {
            case COURSE_TIMELINE_ALLINCLUDINGHIDDEN:
                break;
            case COURSE_TIMELINE_ALL:
                break;
            case COURSE_TIMELINE_PAST:
                break;
            case COURSE_TIMELINE_INPROGRESS:
                break;
            case COURSE_TIMELINE_FUTURE:
                break;
            case COURSE_FAVOURITES:
                break;
            case COURSE_TIMELINE_HIDDEN:
                break;
            case COURSE_CUSTOMFIELD:
                break;
            default:
                throw new invalid_parameter_exception('Invalid classification');
        }
        list($filteredcourses, $processedcount, $favouritecourseids) = static::filter_my_courses(
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
     * Filter my courses (with parameters)
     *
     *
     * @param string $classification
     * @param int $limit
     * @param int $offset
     * @param string|null $sort
     * @param string|null $customfieldname
     * @param string|null $customfieldvalue
     * @param string|null $additionalfilter if set to 'iteach' will display only courses I am registered as a teacher.
     * @return array
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function filter_my_courses(string $classification,
        int $limit = 0,
        int $offset = 0,
        string $sort = null,
        string $customfieldname = null,
        string $customfieldvalue = null,
        string $additionalfilter = null) {
        global $CFG, $PAGE, $USER;
        $requiredproperties = course_summary_exporter::define_properties();
        $fields = join(',', array_keys($requiredproperties));
        $hiddencourses = get_hidden_courses_on_timeline();
        $courses = [];

        // If the timeline requires really all courses, get really all courses.
        if ($classification == COURSE_TIMELINE_ALLINCLUDINGHIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields, COURSE_DB_QUERY_LIMIT);

            // Otherwise if the timeline requires the hidden courses then restrict the result to only $hiddencourses.
        } else if ($classification == COURSE_TIMELINE_HIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, $hiddencourses);

            // Otherwise get the requested courses and exclude the hidden courses.
        } else {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, [], $hiddencourses);
        }

        $favouritecourseids = [];
        $ufservice = \core_favourites\service_factory::get_service_for_user_context(\context_user::instance($USER->id));
        $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');

        if ($favourites) {
            $favouritecourseids = array_map(
                function($favourite) {
                    return $favourite->itemid;
                }, $favourites);
        }

        if ($classification == COURSE_FAVOURITES) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_favourites(
                $courses,
                $favouritecourseids,
                $limit
            );
        } else if ($classification == COURSE_CUSTOMFIELD) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_customfield(
                $courses,
                $customfieldname,
                $customfieldvalue,
                $limit
            );
        } else {
            list($filteredcourses, $processedcount) = course_filter_courses_by_timeline_classification(
                $courses,
                $classification,
                $limit
            );
        }

        if ($additionalfilter == static::COURSE_I_TEACH) {
            list($filteredcourses, $processedcount) = static::course_filter_courses_i_teach(
                $filteredcourses,
                $limit
            );
        }
        return [$filteredcourses, $processedcount, $favouritecourseids];
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function get_enrolled_courses_by_timeline_classification_returns() {
        return core_course_external::get_enrolled_courses_by_timeline_classification_returns();
    }

    /**
     * Filter courses I teach
     *
     * @param array $courses
     * @param int $limit
     * @return array
     * @throws \dml_exception
     */
    protected static function course_filter_courses_i_teach(
        $courses,
        int $limit = 0
    ): array {

        global $USER, $DB, $CFG;
        require_once($CFG->libdir . '/accesslib.php');

        $roleteacherid = $DB->get_field('role', 'id', array('archetype' => 'teacher'));
        $roleeditingteacherid = $DB->get_field('role', 'id', array('archetype' => 'editingteacher'));
        $filteredcourses = [];
        $numberofcoursesprocessed = 0;
        $filtermatches = 0;

        foreach ($courses as $course) {
            $numberofcoursesprocessed++;

            $coursecontext = context_course::instance($course->id);
            if (
                user_has_role_assignment($USER->id, $roleteacherid, $coursecontext->id)
                || user_has_role_assignment($USER->id, $roleeditingteacherid, $coursecontext->id)
            ) {
                $filteredcourses[] = $course;
                $filtermatches++;
            }

            if ($limit && $filtermatches >= $limit) {
                // We've found the number of requested courses. No need to continue searching.
                break;
            }
        }

        // Return the number of filtered courses as well as the number of courses that were searched
        // in order to find the matching courses. This allows the calling code to do some kind of
        // pagination.
        return [$filteredcourses, $numberofcoursesprocessed];
    }

    /**
     * Static filter.
     */
    const COURSE_I_TEACH = 'iteach';
}
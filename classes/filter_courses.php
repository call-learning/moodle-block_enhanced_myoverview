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

use coding_exception;
use context_course;
use core_course\external\course_summary_exporter;
use core_favourites\service_factory;
use dml_exception;
use moodle_exception;

/**
 * Class allowing to filter courses using external API
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_courses {

    /**
     * Static filter.
     */
    const COURSE_I_TEACH = 'iteach';

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
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function filter_my_courses(string $classification,
        int $limit = 0,
        int $offset = 0,
        string $sort = null,
        string $customfieldname = null,
        string $customfieldvalue = null,
        string $additionalfilter = null): array {
        global $USER, $CFG;
        require_once($CFG->dirroot . "/course/lib.php");
        $requiredproperties = course_summary_exporter::define_properties();
        $fields = join(',', array_keys($requiredproperties));
        $hiddencourses = get_hidden_courses_on_timeline();

        // If the timeline requires really all courses, get really all courses.
        if ($classification == COURSE_TIMELINE_ALLINCLUDINGHIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields);

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
        $ufservice = service_factory::get_service_for_user_context(\context_user::instance($USER->id));
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
     * Filter courses I teach
     *
     * @param array $courses
     * @param int $limit
     * @return array
     * @throws dml_exception
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

}

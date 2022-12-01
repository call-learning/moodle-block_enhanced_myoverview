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
 * Block enhanced_myoverview tests are defined here.
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_enhanced_myoverview;

use advanced_testcase;
use coding_exception;

/**
 * Class block_enhanced_myoverview
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 CALL Learning <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enhanced_myoverview_test extends advanced_testcase {
    /**
     * Test course I teach filter
     *
     * @throws coding_exception
     * @covers \block_enhanced_myoverview\external
     */
    public function test_get_group_messages_count() {
        $this->resetAfterTest();
        $generator = $this->getDataGenerator();
        $course1  = $generator->create_course();
        $course2  = $generator->create_course();
        $course3  = $generator->create_course();
        $student1 = $generator->create_user();
        $teacher1 = $generator->create_user();
        $generator->enrol_user($student1->id, $course1->id, 'student');
        $generator->enrol_user($teacher1->id, $course1->id, 'teacher');
        $generator->enrol_user($teacher1->id, $course2->id, 'teacher');
        $generator->enrol_user($teacher1->id, $course3->id, 'student');
        $this->setUser($teacher1);
        list($allcourses, $offset) = external::filter_my_courses("all", 0,
            0, null, null,
            null, external::COURSE_I_TEACH);

        $this->assertCount(2, $allcourses);
        $allcoursesid = array_map(function($c) {
            return $c->id;
        }, $allcourses);
        $this->assertContains($course1->id, $allcoursesid);
        $this->assertContains($course2->id, $allcoursesid);
        $this->setUser($student1);
        list($allcourses, $offset) = external::filter_my_courses("all", 0,
            0, null, null,
            null, external::COURSE_I_TEACH);

        $this->assertEquals([], $allcourses);
    }

}

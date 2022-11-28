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
 * Class containing data for my enhanced overview block.
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_enhanced_myoverview\output;
use block_enhanced_myoverview\external;
use coding_exception;
use renderer_base;

/**
 * Class containing data for my overview block.
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class main extends \block_myoverview\output\main {
    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array Context variables for the template
     * @throws coding_exception
     *
     */
    public function export_for_template(renderer_base $output) {
        $context = parent::export_for_template($output);
        $context['additionalfilter'] = external::COURSE_I_TEACH;
        return $context;
    }
}

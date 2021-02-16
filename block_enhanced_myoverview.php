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
 * Contains the class for the My overview block (enhanced version).
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot. '/blocks/myoverview/block_myoverview.php');
/**
 * My overview block class.
 *
 * @package     block_enhanced_myoverview
 * @copyright   2021 Laurent David <laurent@call-learning.fr>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_enhanced_myoverview extends block_myoverview {

    /**
     * Init.
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_enhanced_myoverview');
    }

    /**
     * Returns the contents.
     *
     * @return stdClass contents of block
     */
    public function get_content() {
        if (isset($this->content)) {
            return $this->content;
        }
        $group = get_user_preferences('block_myoverview_user_grouping_preference');
        $sort = get_user_preferences('block_myoverview_user_sort_preference');
        $view = get_user_preferences('block_myoverview_user_view_preference');
        $paging = get_user_preferences('block_myoverview_user_paging_preference');
        $customfieldvalue = get_user_preferences('block_myoverview_user_grouping_customfieldvalue_preference');

        $renderable = new \block_enhanced_myoverview\output\main($group, $sort, $view, $paging, $customfieldvalue);
        $renderer = $this->page->get_renderer('block_enhanced_myoverview');

        $this->content = new stdClass();
        $this->content->text = $renderer->render($renderable);
        $this->content->footer = '';

        return $this->content;
    }

    /**
     * Defines configuration data.
     *
     * The function is called immediatly after init().
     */
    public function specialization() {

        // Load user defined title and make sure it's never empty.
        if (empty($this->config->title)) {
            $this->title = get_string('pluginname', 'block_enhanced_myoverview');
        } else {
            $this->title = $this->config->title;
        }
    }
}


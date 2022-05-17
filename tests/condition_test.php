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
 * Unit tests for the auth condition.
 *
 * @package availability_auth
 * @copyright 2022 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @copyright 2021 Roberto Pinna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace availability_auth;

use \core_availability\mock_info;
use \core_availability\tree;
use availability_auth\condition;
use moodle_exception;

/**
 * Unit tests for the auth condition.
 *
 * @package   availability_auth
 * @copyright 2022 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @copyright 2021 Roberto Pinna
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \availability_auth
 */
class condition_test extends \advanced_testcase {

    /**
     * Load required classes.
     */
    public function setUp():void {
        // Load the mock info class so that it can be used.
        global $CFG;
        require_once($CFG->dirroot . '/availability/tests/fixtures/mock_info.php');
    }

    /**
     * Tests constructing and using auth condition as part of tree.
     * @covers \availability_auth\condition
     */
    public function test_in_tree() {
        global $DB;
        $this->resetAfterTest();

        // Create course with auth turned on and a Page.
        set_config('enableavailability', true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user1 = $generator->create_user()->id;
        $user2 = $generator->create_user()->id;
        $DB->set_field('user', 'auth', 'email', ['id' => $user2]);

        $info1 = new mock_info($course, $user1);
        $info2 = new mock_info($course, $user2);

        $arr1 = ['type' => 'auth', 'id' => 'manual'];
        $arr2 = ['type' => 'auth', 'id' => 'email'];
        $tree1 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr1]]);
        $tree2 = new \core_availability\tree((object)['op' => '|', 'show' => true, 'c' => [(object)$arr2]]);

        // Initial check.
        $this->setAdminUser();
        $this->assertTrue($tree1->check_available(false, $info1, true, null)->is_available());
        $this->assertFalse($tree1->check_available(false, $info1, true, $user1)->is_available());
        $this->assertTrue($tree2->check_available(false, $info1, true, $user1)->is_available());
        $this->assertTrue($tree1->check_available(false, $info1, true, $user2)->is_available());
        $this->assertFalse($tree2->check_available(false, $info1, true, $user2)->is_available());
        $this->assertFalse($tree1->check_available(false, $info2, true, $user1)->is_available());
        $this->assertTrue($tree2->check_available(false, $info2, true, $user1)->is_available());
        $this->assertTrue($tree1->check_available(false, $info2, true, $user2)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $user2)->is_available());
        // Change user.
        $this->setuser($user1);
        $this->assertTrue($tree1->check_available(false, $info1, true, $user1)->is_available());
        $this->assertFalse($tree2->check_available(false, $info1, true, $user1)->is_available());
        $this->assertFalse($tree1->check_available(true, $info1, true, $user1)->is_available());
        $this->assertTrue($tree2->check_available(true, $info1, true, $user1)->is_available());
        $this->setuser($user2);
        $this->assertTrue($tree1->check_available(false, $info2, true, $user2)->is_available());
        $this->assertFalse($tree2->check_available(false, $info2, true, $user2)->is_available());
        $this->assertFalse($tree1->check_available(true, $info2, true, $user2)->is_available());
        $this->assertTrue($tree2->check_available(true, $info2, true, $user2)->is_available());
    }

    /**
     * Tests section availability.
     * @covers \availability_auth\condition
     */
    public function test_sections() {
        global $DB;
        $this->resetAfterTest();
        set_config('enableavailability', true);
        // Create course with auth turned on and a Page.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $user1 = $generator->create_user()->id;
        $DB->set_field('user', 'auth', 'email', ['id' => $user1]);
        $user2 = $generator->create_user()->id;
        $generator->enrol_user($user1, $course->id);
        $generator->enrol_user($user2, $course->id);
        $cond = '{"op":"|","show":false,"c":[{"type":"auth","id":"email"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $course->id, 'section' => 0]);
        $cond = '{"op":"|","show":true,"c":[{"type":"auth","id":""}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $course->id, 'section' => 1]);
        $cond = '{"op":"|","show":true,"c":[{"type":"auth","id":"db"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $course->id, 'section' => 2]);
        $cond = '{"op":"|","show":true,"c":[{"type":"auth","id":"manual"}]}';
        $DB->set_field('course_sections', 'availability', $cond, ['course' => $course->id, 'section' => 3]);
        $modinfo1 = get_fast_modinfo($course, $user1);
        $modinfo2 = get_fast_modinfo($course, $user2);
        $this->assertTrue($modinfo1->get_section_info(0)->uservisible);
        $this->assertTrue($modinfo1->get_section_info(1)->uservisible);
        $this->assertFalse($modinfo1->get_section_info(2)->uservisible);
        $this->assertFalse($modinfo1->get_section_info(3)->uservisible);
        $this->assertFalse($modinfo2->get_section_info(0)->uservisible);
        $this->assertTrue($modinfo2->get_section_info(1)->uservisible);
        $this->assertFalse($modinfo2->get_section_info(2)->uservisible);
        $this->assertTrue($modinfo2->get_section_info(3)->uservisible);
    }

    /**
     * Tests the constructor including error conditions.
     * @covers \availability_auth\condition
     */
    public function test_constructor() {
        // This works with no parameters.
        $structure = (object)[];
        $auth = new condition($structure);
        $this->assertNotEmpty($auth);

        // Invalid ->id.
        $auth = null;
        $structure->id = null;
        try {
            $auth = new condition($structure);
        } catch (\coding_exception $e) {
            $this->assertStringContainsString('Invalid ->id for authentication condition', $e->getMessage());
        }
        $structure->id = 12;
        try {
            $auth = new condition($structure);
        } catch (\coding_exception $e) {
            $this->assertStringContainsString('Invalid ->id for authentication condition', $e->getMessage());
        }
        $this->assertEquals(null, $auth);
    }

    /**
     * Tests the save() function.
     * @covers \availability_auth\condition
     */
    public function test_save() {
        $structure = (object)['id' => 'db'];
        $cond = new condition($structure);
        $structure->type = 'auth';
        $this->assertEqualsCanonicalizing($structure, $cond->save());
        $this->assertEqualsCanonicalizing((object)['type' => 'auth', 'id' => 'email'], $cond->get_json('email'));
    }

    /**
     * Tests the get_description and get_standalone_description functions.
     * @covers \availability_auth\condition
     */
    public function test_get_description() {
        $info = new mock_info();
        $auth = new condition((object)['type' => 'auth', 'id' => '']);
        $this->assertEquals($auth->get_description(false, false, $info), '');
        $auth = new condition((object)['type' => 'auth', 'id' => 'manual']);
        $desc = $auth->get_description(true, false, $info);
        $this->assertEquals('The user\'s authentication is Manual accounts', $desc);
        $desc = $auth->get_description(true, true, $info);
        $this->assertEquals('The user\'s auth is not Manual ‎(manual)‎', $desc);
        $desc = $auth->get_standalone_description(true, false, $info);
        $this->assertStringContainsString('Not available unless: The user\'s auth is Manual', $desc);
        $result = phpunit_util::call_internal_method($auth, 'get_debug_string', [], 'availability_auth\condition');
        $this->assertEquals('en', $result);
    }

    /**
     * Tests using auth condition in front end.
     * @covers \availability_auth\frontend
     */
    public function test_frontend() {
        global $CFG;
        require_once($CFG->dirroot.'/mod/lesson/locallib.php');
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $les = new \lesson($generator->get_plugin_generator('mod_lesson')->create_instance(['course' => $course, 'section' => 0]));
        $user = $generator->create_user();
        $modinfo = get_fast_modinfo($course);
        $cm = $modinfo->get_cm($les->cmid);
        $sections = $modinfo->get_section_info_all();
        $generator->enrol_user($user->id, $course->id);

        $name = 'availability_auth\frontend';
        $frontend = new \availability_auth\frontend();
        // There is only 1 auth enabled, so we cannot assert allow add will return true.
        $this->assertCount(1, \get_enabled_auths());
        $this->assertCount(1, phpunit_util::call_internal_method($frontend, 'get_javascript_init_params', [$course], $name));
        $this->assertFalse(phpunit_util::call_internal_method($frontend, 'allow_add', [$course], $name));
        $this->assertFalse(phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm, null], $name));
        $this->assertFalse(phpunit_util::call_internal_method($frontend, 'allow_add', [$course, $cm, $sections[1]], $name));
        $this->assertFalse(phpunit_util::call_internal_method($frontend, 'allow_add', [$course, null, $sections[0]], $name));
        $this->assertFalse(phpunit_util::call_internal_method($frontend, 'allow_add', [$course, null, $sections[1]], $name));
    }


    /**
     * Tests using auth condition in back end.
     * @covers \availability_auth\condition
     */
    public function test_backend() {
        global $CFG, $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enableavailability', true);
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $context = \context_course::instance($course->id);
        $user = $generator->create_user();
        $generator->enrol_user($user->id, $course->id);
        $pagegen = $generator->get_plugin_generator('mod_page');
        $restriction = \core_availability\tree::get_root_json([condition::get_json('db')]);
        $pagegen->create_instance(['course' => $course, 'availability' => json_encode($restriction)]);
        $restriction = \core_availability\tree::get_root_json([condition::get_json('manual')]);
        $pagegen->create_instance(['course' => $course, 'availability' => json_encode($restriction)]);
        $restriction = \core_availability\tree::get_root_json([condition::get_json('email')]);
        $pagegen->create_instance(['course' => $course, 'availability' => json_encode($restriction)]);
        rebuild_course_cache($course->id, true);
        $mpage = new \moodle_page();
        $mpage->set_url('/course/index.php', ['id' => $course->id]);
        $mpage->set_context($context);
        $format = course_get_format($course);
        $renderer = $mpage->get_renderer('format_topics');
        $branch = (int)$CFG->branch;
        if ($branch > 311) {
            $outputclass = $format->get_output_classname($branch == 311 ? 'course_format' : 'content');
            $output = new $outputclass($format);
            ob_start();
            echo $renderer->render($output);
        } else {
            ob_start();
            echo $renderer->print_multiple_section_page($course, null, null, null, null);
        }
        $out = ob_get_clean();
        $this->assertStringContainsString('Not available unless: The user\'s auth is Manual', $out);
        // MDL-68333 hack when nl auth is not installed.
        $DB->set_field('user', 'auth', 'db', ['id' => $user->id]);
        $this->setuser($user);
        rebuild_course_cache($course->id, true);
        ob_start();
        if ($branch > 311) {
            echo $renderer->render($output);
        } else {
            echo $renderer->print_multiple_section_page($course, null, null, null, null);
        }
        $out = ob_get_clean();
        $this->assertStringNotContainsString('Not available unless: The user\'s auth is Manual', $out);
    }
}

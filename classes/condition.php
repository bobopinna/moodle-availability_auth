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
 * Condition main class.
 *
 * @package availability_auth
 * @copyright 2022 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @copyright 2021 Roberto Pinna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_auth;

/**
 * Condition main class.
 *
 * @package availability_auth
 * @copyright 2022 eWallah.net
 * @author    Renaat Debleu <info@eWallah.net>
 * @copyright 2021 Roberto Pinna
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class condition extends \core_availability\condition {

    /** @var string of authentication that this condition requires, or '' = any authentication */
    protected $auth;

    /**
     * Constructor.
     *
     * @param \stdClass $structure Data structure from JSON decode
     * @throws \coding_exception If invalid data structure.
     */
    public function __construct($structure) {
        // Get auth.
        if (!property_exists($structure, 'id')) {
            $this->auth = '';
        } else if (is_string($structure->id)) {
            $this->auth = $structure->id;
        } else {
            throw new \coding_exception('Invalid ->id for authentication condition');
        }
    }

    /**
     * Saves data back to a structure object.
     *
     * @return \stdClass Structure object
     */
    public function save() {
        $result = (object)['type' => 'auth'];
        if ($this->auth) {
            $result->id = $this->auth;
        }
        return $result;
    }

    /**
     * Returns a JSON object which corresponds to a condition of this type.
     *
     * Intended for unit testing, as normally the JSON values are constructed
     * by JavaScript code.
     *
     * @param string $auth Not required authentication
     * @return stdClass Object representing condition
     */
    public static function get_json($auth = '') {
        return (object)['type' => 'auth', 'id' => $auth];
    }

    /**
     * Determines whether a particular item is currently available
     * according to this availability condition.
     *
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @param bool $grabthelot Performance hint: if true, caches information
     *   required for all course-modules, to make the front page and similar
     *   pages work more quickly (works only for current user)
     * @param int $userid User ID to check availability for
     * @return bool True if available
     */
    public function is_available($not, \core_availability\info $info, $grabthelot, $userid) {
        global $CFG, $DB, $USER;

        $allow = false;
        $userauth = 'manual';
        if (($userid == $USER->id) && isset($USER->auth)) {
            // Checking the authenthication method of the currently logged in user, so do not
            // default to the account authentication, because the session authentication may be different.
            $userauth = $USER->auth;
        } else {
            if (!is_null($userid)) {
                // Checking access for someone else than the logged in user, so
                // use the authentication of that user account.
                // This authentication is never empty as there is a not-null constraint.
                $userauth = $DB->get_field('user', 'auth', ['id' => $userid]);
            }
        }
        if ($userauth == $this->auth) {
            $allow = true;
        }
        if ($not) {
            $allow = !$allow;
        }
        return $allow;
    }

    /**
     * Obtains a string describing this restriction (whether or not
     * it actually applies). Used to obtain information that is displayed to
     * students if the activity is not available to them, and for staff to see
     * what conditions are.
     *
     * @param bool $full Set true if this is the 'full information' view
     * @param bool $not Set true if we are inverting the condition
     * @param info $info Item we're checking
     * @return string Information string (for admin) about all restrictions on this item
     */
    public function get_description($full, $not, \core_availability\info $info) {
        if ($this->auth != '') {
            $enabledauths = $this->get_enabled_auths();
            if (array_key_exists($this->auth, $enabledauths)) {
                $snot = $not ? 'not' : '';
                return get_string('getdescription' .$snot, 'availability_auth', $enabledauths[$this->auth]);
            }
        }
        return '';
    }

    /**
     * Obtains a representation of the options of this condition as a string,
     * for debugging.
     *
     * @return string Text representation of parameters
     */
    protected function get_debug_string() {
        return $this->auth ?? 'any';
    }

    /**
     * Obtains an array of all enabled authentication methods.
     *
     * @return array List of enabled authentication methods
     */
    public static function get_enabled_auths() {
        $auths = array_keys(\core_component::get_plugin_list('auth'));

        $enabledauths = array();
        foreach ($auths as $auth) {
            // No login account can not login.
            if (is_enabled_auth($auth) && $auth != 'nologin') {
                 $enabledauths[$auth] = get_string('pluginname', "auth_{$auth}");
            }
        }

        return $enabledauths;
    }
}

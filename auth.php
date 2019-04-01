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
 * @author Erlend Strømsvik - Ny Media AS
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package auth_saml
 * @version 1.0
 *
 * Authentication Plugin: SAML based SSO Authentication
 *
 * Authentication using SAML2 with SimpleSAMLphp.
 *
 * Based on plugins made by Sergio Gómez (moodle_ssp) and Martin Dougiamas (Shibboleth).
 *
 * 2008-10  Created
 * 2009-07  added new configuration options.  Tightened up the session handling
 **/

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
}

require_once($CFG->libdir.'/authlib.php');

/**
 * SimpleSAML authentication plugin.
 **/
class auth_plugin_saml extends auth_plugin_base {

    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'saml';
        $this->config = get_config('auth_saml');
        $this->get_custom_user_profile_fields();
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist.
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $SESSION;
        // if true, user_login was initiated by saml/index.php
        if (isset($SESSION->auth_saml_login) && $SESSION->auth_saml_login) {
            unset($SESSION->auth_saml_login);
            return true;
        }
        return false;
    }


    /**
     * Returns the user information for 'external' users. In this case the
     * attributes provided by Identity Provider
     *
     * @return array $result Associative array of user data
     */
    public function get_userinfo($username) {
        global $SESSION;
        if ($login_attributes = $SESSION->auth_saml_login_attributes) {
            $attributemap = $this->get_attributes();
	        $result = array();

            foreach ($attributemap as $key => $value) {
                if (isset($login_attributes[$value]) && $attribute = $login_attributes[$value][0]) {
                    $result[$key] = $attribute;
                } else {
                    $result[$key] = '';
                }
            }

	        unset($SESSION->auth_saml_login_attributes);

	        $result["username"] = $username;
	        return $result;
	    }

	    return false;
    }

    /*
    * Returns array containg attribute mappings between Moodle and Identity Provider.
    */
    public function get_attributes() {
        $configarray = (array) $this->config;

        if (isset($this->userfields)) {
            $fields = $this->userfields;
        } else {
            $fields = array("firstname", "lastname", "email", "phone1", "phone2",
			    "department", "address", "city", "country", "description",
			    "idnumber", "lang", "guid", "web", "skype", "yahoo", "msn",
                            "aim", "icq");
        }

        $fields = array_merge($fields, $this->get_custom_user_profile_fields());

        $moodleattributes = array();
        foreach ($fields as $field) {
            if (isset($configarray["field_map_$field"])) {
	        $moodleattributes[$field] = $configarray["field_map_$field"];
            }
        }

        return $moodleattributes;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
	    return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
	    return false;
    }

    public function pre_loginpage_hook() {
        // If Force Login is on then we can safely jump directly to the SAML IdP
        if (isset($this->config->autologin) && $this->config->autologin) {
            global $CFG, $SESSION;
            $samlurl = $CFG->wwwroot.'/auth/saml/index.php?wantsurl=' . urlencode($SESSION->wantsurl);
            redirect($samlurl);
        }
    }

    public function loginpage_hook() {
	    global $CFG;

        if (empty($CFG->alternateloginurl) && !(isset($_GET['saml']) && $_GET['saml'] === 'false')) {
            $CFG->alternateloginurl = $CFG->wwwroot.'/auth/saml/login.php';
        }

	    // Prevent username from being shown on login page after logout
	    $CFG->nolastloggedin = true;
    }

    public function logoutpage_hook() {
        global $CFG;

	    if (isset($this->config->dosinglelogout) && $this->config->dosinglelogout) {
	        set_moodle_cookie('nobody');
	        require_logout();
	        redirect($CFG->wwwroot.'/auth/saml/index.php?logout=1');
	    }
    }

    /**
     * Cleans and returns first of potential many values (multi-valued attributes)
     *
     * @param string $string Possibly multi-valued attribute from Identity Provider
     */
    public function get_first_string($string) {
        $list = preg_split( ';', $string);
        $cleanstring = trim($list[0]);

        return $cleanstring;
    }


  /**
   * Create course_mapping table in Moodle database.
   *
   */
    function create_course_mapping_db($DB, &$err) {

        $table = $this->get_course_mapping_xmldb();

        $sucess = false;
        try {
            $dbman = $DB->get_manager();
            $dbman->create_table($table);
	        echo '<span class="notifysuccess">';
	        print_string("auth_saml_sucess_creating_course_mapping", "auth_saml");
	        echo '</span><br>';
            $sucess = true;
        } catch (Exception $e) {
            $err['course_mapping_db'][] = get_string("auth_saml_error_creating_course_mapping", "auth_saml");
        }
        return $sucess;
    }

    /**
     * Create role_mapping table in Moodle database.
     *
     */
    function create_role_mapping_db($DB, &$err) {

        $table = $this->get_role_mapping_xmldb();

        $sucess = false;
        try {
            $dbman = $DB->get_manager();
            $dbman->create_table($table);
	        echo '<span class="notifysuccess">';
	        print_string("auth_saml_sucess_creating_role_mapping", "auth_saml");
	        echo '</span><br>';
        } catch (Exception $e) {
	        $err['role_mapping_db'][] = get_string("auth_saml_error_creating_role_mapping", "auth_saml");
        }
        return $sucess;
    }

    function get_course_mapping_xmldb() {

        $table = new xmldb_table('course_mapping');

        $table->add_field('course_mapping_id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
		$table->add_field('saml_course_id', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null);
		$table->add_field('saml_course_period', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, null, null);
		$table->add_field('lms_course_id', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('course_mapping_id'));

        return $table;
    }

    function get_role_mapping_xmldb() {
        $table = new xmldb_table('role_mapping');

		$table->add_field('saml_role', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null, null);
		$table->add_field('lms_role', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('saml_role'));

        return $table;
    }


    public function initialize_roles($DB, &$err) {

	    $sqls = array();
	    $sqls[] = "DELETE FROM role_mapping;";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('editingteacher','teacher')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('editingteacher','instructor')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('editingteacher','mentor')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('student','student')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('student','learner')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('user','member')";
	    $sqls[] = "INSERT INTO role_mapping (lms_role, saml_role) values ('admin','admin')";

        $sucess = true;
	    foreach ($sqls as $sql) {
            try {
	            $DB->execute($sql);
            } catch (Exception $e) {
    		    $err['role_mapping_db'][] = get_string("auth_saml_error_creating_role_mapping", "auth_saml");
                $sucess = false;
    		    break;
            }
	    }
        return $sucess;
    }
}

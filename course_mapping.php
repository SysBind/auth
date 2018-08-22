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

defined('MOODLE_INTERNAL') || die;

$role_mapping = array();
$course_mapping = array();
try {
    $config = get_config('auth_saml');
    require_once("roles.php");
    require_once("courses.php");
    // disable the course mapping for now
    //$role_mapping = get_role_mapping_for_sync($err, $config);
    //$course_mapping = get_course_mapping_for_sync($err, $config);
} catch (Exception $e) {
    print_error('Caught exception while mapping: '.  $e->getMessage(). "\n");
}

$mapped_roles = array_unique(array_values($role_mapping));
$mapped_courses = array();

foreach ($saml_courses as $key => $course) {
    if (function_exists('saml_hook_get_course_info')) {
         $regs = saml_hook_get_course_info($course);
         if ($regs) {

            list($match, $country, $domain, $course_id, $period, $role, $status) = $regs;

            if (isset($role_mapping[$role]) && isset($course_mapping[$course_id][$period])) {
                $mapped_role = $role_mapping[$role];
                $mapped_course_id = $course_mapping[$course_id][$period];
                $mapped_courses[$mapped_role][$status][$mapped_course_id] = array( 'country' => $country,
                                                        'domain' => $domain,
                                                        'course_id' => $mapped_course_id,
                                                        'period' => $period,
                                                        'role' => $mapped_role,
                                                        'status' => $status,
                );
                if (!$any_course_active && $status == 'active') {
                      $any_course_active = true;
                }
            } else {
                $str_obj = new stdClass();
                $str_obj->course = '('.$course_id.' -- '.$period.')';
                $str_obj->user = $saml_user_identify;
                $err['course_enrollment'][] = get_string('auth_saml_course_not_found' , 'auth_saml', $str_obj);
            }
         }
    }
}

unset($saml_courses);
unset($saml_user_identify);

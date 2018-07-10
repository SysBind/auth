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

defined('MOODLE_INTERNAL') || die();

function process_settigs() {
    global $CFG;
    $config = get_config('auth_saml');

    // SAML parameters are in the config variable due all form data is there.
    // We create a new variable and set the values there.
    $samlparam = new stdClass();

    if (!isset ($config->samllib)) {
        $samlparam->samllib = '';
    }
    else {
        $samlparam->samllib = $config->samllib;
    }
    if (!isset ($config->sp_source)) {
        $samlparam->sp_source = 'saml';
    }
    else {
        $samlparam->sp_source = $config->sp_source;
    }
    if (!isset ($config->dosinglelogout)) {
        $samlparam->dosinglelogout = false;
    }
    else {
        $samlparam->dosinglelogout = $config->dosinglelogout;
    }

    if (!isset ($config->logouturl)) {
        $samlparam->logouturl = '';
    }
    else {
        $samlparam->logouturl = $config->logouturl;
    }
    // Save saml settings in a file
    $saml_param_encoded = json_encode($samlparam);
    file_put_contents($CFG->dataroot.'/saml_config.php', $saml_param_encoded);
}
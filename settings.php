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

require_once('locallib.php');

if ($ADMIN->fulltree) {

    global $CFG, $OUTPUT;

    require_once("courses.php");
    require_once("roles.php");

    // Get saml paramters stored in the saml_config.php
    if (file_exists($CFG->dataroot.'/saml_config.php')) {
        $contentfile = file_get_contents($CFG->dataroot.'/saml_config.php');
        $samlparam = json_decode($contentfile);
    } else if (file_exists('saml_config.php')) {
        $contentfile = file_get_contents('saml_config.php');
        $samlparam = json_decode($contentfile);
    } else {
        $samlparam = new stdClass();
    }

    $config = get_config('auth_saml');

    // Set to defaults if undefined.
    if (!isset ($samlparam->samllib)) {
        if (isset ($config->samllib)) {
            $samlparam->samllib = $config->samllib;
        } else {
            $samlparam->samllib = '/var/www/sp/simplesamlphp/lib';
        }
    }
    if (!isset ($samlparam->sp_source)) {
        if (isset ($config->sp_source)) {
            $samlparam->sp_source = $config->sp_source;
        } else {
            $samlparam->sp_source = 'default-sp';
        }
    }
    if (!isset ($samlparam->dosinglelogout)) {
        if (isset ($config->dosinglelogout)) {
            $samlparam->dosinglelogout = $config->dosinglelogout;
        } else {
            $samlparam->dosinglelogout = false;
        }
    }
    if (!isset($samlparam->username)) {
        if (isset($config->username)) {
            $samlparam->username = $config->username;
        } else {
            $samlparam->username = 'eduPersonPrincipalName';
        }
    }
    if (!isset ($samlparam->notshowusername)) {
        if (isset($config->notshowusername)) {
            $samlparam->notshowusername = $config->notshowusername;
        } else {
            $samlparam->notshowusername = 'none';
        }
    }
    if (!isset ($samlparam->supportcourses)) {
        if (isset($config->supportcourses)) {
            $samlparam->supportcourses = $config->supportcourses;
        } else {
            $samlparam->supportcourses = 'nosupport';
        }
    }
    if (!isset ($samlparam->syncusersfrom)) {
        if (isset($config->syncusersfrom)) {
            $samlparam->syncusersfrom = $config->syncusersfrom;
        } else {
            $samlparam->syncusersfrom = '';
        }
    }
    if (!isset ($samlparam->samlcourses)) {
        if (isset($config->samlcourses)) {
            $samlparam->samlcourses = $config->samlcourses;
        } else {
            $samlparam->samlcourses = 'schacUserStatus';
        }
    }
    if (!isset ($samlparam->samllogoimage)) {
        if (isset($config->samllogoimage)) {
            $samlparam->samllogoimage = $config->samllogoimage;
        } else {
            $samlparam->samllogoimage = 'logo.gif';
        }
    }
    if (!isset ($samlparam->samllogoinfo)) {
        if (isset($config->samllogoinfo)) {
            $samlparam->samllogoinfo = $config->samllogoinfo;
        } else {
            $samlparam->samllogoinfo = 'SAML login';
        }
    }
    if (!isset ($samlparam->autologin)) {
        if (isset($config->autologin)) {
            $samlparam->autologin = $config->autologin;
        } else {
            $samlparam->autologin = false;
        }
    }
    if (!isset ($samlparam->samllogfile)) {
        if (isset($config->samllogfile)) {
            $samlparam->samllogfile = $config->samllogfile;
        } else {
            $samlparam->samllogfile = '';
        }
    }
    if (!isset ($samlparam->samlhookfile)) {
        if (isset($config->samlhookfile)) {
            $samlparam->samlhookfile = $config->samlhookfile;
        } else {
            $samlparam->samlhookfile = $CFG->dirroot . '/auth/saml/custom_hook.php';
        }
    }
    if (!isset ($config->moodlecoursefieldid)) {
        $config->moodlecoursefieldid = 'shortname';
    }
    if (!isset ($config->ignoreinactivecourses)) {
        $config->ignoreinactivecourses = true;
    }
    if (!isset ($config->externalcoursemappingdsn)) {
        $config->externalcoursemappingdsn = '';
    }
    if (!isset ($config->externalrolemappingdsn)) {
        $config->externalrolemappingdsn = '';
    }
    if (!isset ($config->externalcoursemappingsql)) {
        $config->externalcoursemappingsql = '';
    }
    if (!isset ($config->externalrolemappingsql)) {
        $config->externalrolemappingsql = '';
    }

    if (!isset ($samlparam->disablejit)) {
        if (isset($config->disablejit)) {
            $samlparam->disablejit = $config->disablejit;
        } else {
            $samlparam->disablejit = false;
        }
    }

    // Introductory explanation.
    $settings->add(new admin_setting_heading('auth_saml/pluginname', '', new lang_string('auth_samldescription', 'auth_saml')));

    // samllib folder.
    $setting = new admin_setting_configtext('auth_saml/samllib', get_string('auth_saml_samllib', 'auth_saml'),
        get_string('auth_saml_samllib_description', 'auth_saml') , $samlparam->samllib);
    $setting->set_updatedcallback('process_settigs');
    $settings->add($setting);

    // sp_source.
    $setting = new admin_setting_configtext('auth_saml/sp_source', get_string('auth_saml_sp_source', 'auth_saml'),
        get_string('auth_saml_sp_source_description', 'auth_saml') , $samlparam->sp_source);
    $setting->set_updatedcallback('process_settigs');
    $settings->add($setting);

    // username.
    $settings->add(new admin_setting_configtext('auth_saml/username', get_string('auth_saml_username', 'auth_saml'),
        get_string('auth_saml_username_description', 'auth_saml') , $samlparam->username));

    // dosinglelogout.
    $setting = new admin_setting_configcheckbox('auth_saml/dosinglelogout', get_string('auth_saml_dosinglelogout', 'auth_saml'),
        get_string('auth_saml_dosinglelogout_description', 'auth_saml') , $samlparam->dosinglelogout);
    $setting->set_updatedcallback('process_settigs');
    $settings->add($setting);

    $setting = new admin_setting_configtext('auth_saml/logouturl', get_string('auth_saml_logouturl', 'auth_saml'),
        get_string('auth_saml_logouturl_desc', 'auth_saml'), get_string('defaultlogouturl', 'auth_saml'));
    $setting->set_updatedcallback('process_settigs');
    $settings->add($setting);
    // samllogoimage.
    $settings->add(new admin_setting_configtext('auth_saml/samllogoimage', get_string('auth_saml_logo_path', 'auth_saml'),
        get_string('auth_saml_logo_path_description', 'auth_saml') , $samlparam->samllogoimage));

    // samllogoinfo.
    $settings->add(new admin_setting_configtextarea('auth_saml/samllogoinfo', get_string('auth_saml_logo_info', 'auth_saml'),
        get_string('auth_saml_logo_info_description', 'auth_saml') , $samlparam->samllogoinfo));

    // autologin.
    $settings->add(new admin_setting_configcheckbox('auth_saml/autologin', get_string('auth_saml_autologin', 'auth_saml'),
        get_string('auth_saml_autologin_description', 'auth_saml') , $samlparam->autologin));

    // samllogfile.
    $settings->add(new admin_setting_configtext('auth_saml/samllogfile', get_string('auth_saml_logfile', 'auth_saml'),
        get_string('auth_saml_logfile_description', 'auth_saml') , $samlparam->samllogfile));

    // samlhookfile.
    $settings->add(new admin_setting_configtext('auth_saml/samlhookfile', get_string('auth_saml_samlhookfile', 'auth_saml'),
        get_string('auth_saml_samlhookfile_description', 'auth_saml') , $samlparam->samlhookfile));

    // disablejit.
    $settings->add(new admin_setting_configcheckbox('auth_saml/disablejit', get_string('auth_saml_disablejit', 'auth_saml'),
        get_string('auth_saml_disablejit_description', 'auth_saml') , $samlparam->disablejit));

    // Display locking / mapping of profile fields.
    $authplugin = get_auth_plugin('saml');
    display_auth_lock_options($settings, $authplugin->authtype, $authplugin->userfields,
        get_string('auth_fieldlocks_help', 'auth'), true, false, $authplugin->customfields);
}
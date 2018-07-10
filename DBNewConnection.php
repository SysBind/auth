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

function dbnewconnection($dsn) {
    global $CFG;
    require_once($CFG->libdir.'/adodb/adodb.inc.php');

    $cdata = explode('://', $dsn);
    $scheme = $cdata[0];

    if ($scheme === 'sqlite') {
        $path = $cdata[1];
        $mapping_db = ADONewConnection('sqlite');
        $conn = $mapping_db->PConnect($path);
    } else {
        $mapping_db = ADONewConnection($dsn);
    }
    return $mapping_db;
}

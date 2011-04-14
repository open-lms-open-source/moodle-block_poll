<?php  //$Id$

// This file keeps track of upgrades to
// the poll block
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_block_poll_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;

    $result = true;

    /// Add a new column for anonymous polls
    if ($result && $oldversion < 2011041400) {
        $table = new XMLDBTable('block_poll');
        $field = new XMLDBField('anonymous');
        $field->setAttributes(XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, '0', 'anonymous');

        $result = $result && add_field($table, $field);
    }

    return $result;
}

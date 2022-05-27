<?php

defined('MOODLE_INTERNAL') || die();

function xmldb_message_discord_install() {
    global $DB;
    $result = true;

    $provider = new stdClass();
    $provider->name  = 'discord';
    $DB->insert_record('message_processors', $provider);
    return $result;
}

<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configtext('botname', get_string('botname', 'message_discord'), get_string('configbotname', 'message_discord'), '', PARAM_RAW));
}
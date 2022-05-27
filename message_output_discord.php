<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.com                                            //
//                                                                       //
// Copyright (C) 1999 onwards  Martin Dougiamas  http://moodle.com       //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////


require_once($CFG->dirroot.'/message/output/lib.php');

class message_output_discord extends message_output {

    function send_message($eventdata) {
        global $CFG;

        if (($eventdata->userto->auth === 'nologin') || $eventdata->userto->suspended || $eventdata->userto->deleted) {
            return true;
        }

        if (!empty($CFG->noemailever)) {
            debugging('$CFG->noemailever is active, no discord message sent.', DEBUG_MINIMAL);
            return true;
        }

        static $webhooks = array();

        if (!array_key_exists($eventdata->userto->id, $webhooks)) {
            $webhooks[$eventdata->userto->id] = get_user_preferences('message_processor_webhook_url', null, $eventdata->userto->id);
        }
        $webhook = $webhooks[$eventdata->userto->id];

        $discordmessage = "Сообщение из Moodle"."\n".fullname($eventdata->userfrom).': '.$eventdata->smallmessage;

        if (!empty($eventdata->contexturl)) {
            $discordmessage .= "\n".get_string('view').': '.$eventdata->contexturl;
        }

        $botname = $CFG->botname;

        $json_data = json_encode([
            "content" => $discordmessage,
            "username" => $botname
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $ch = curl_init($webhook);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($ch);
        curl_close($ch);

        return true;
    }

    public function config_form($preferences) {
        global $CFG;

        if (!$this->is_system_configured()) {
            return get_string('notconfigured','message_discord');
        } else {
            return get_string('webhook', 'message_discord').': <input size="30" name="webhook_url" value="'.($preferences->webhook_url).'" />';
        }
    }

    public function process_form($form, &$preferences) {
        if (isset($form->webhook_url) && !empty($form->webhook_url)) {
            $preferences['message_processor_webhook_url'] = $form->webhook_url;
        }
    }

    public function load_data(&$preferences, $userid) {
        $preferences->webhook_url = get_user_preferences( 'message_processor_webhook_url', '', $userid);
    }

    public function is_system_configured($user = null) {
        global $CFG;
        return (!empty($CFG->botname));
    }

    public function is_user_configured($user = null) {
        global $USER;

        if (is_null($user)) {
            $user = $USER;
        }
        return (bool) get_user_preferences('message_processor_webhook_url', null, $user->id);
    }
}

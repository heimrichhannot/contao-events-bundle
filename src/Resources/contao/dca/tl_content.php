<?php

// Dynamically add the permission check and parent table
if (Input::get('do') == 'calendar_subevents') {
    $dca = &$GLOBALS['TL_DCA']['tl_content'];

    $dca['config']['ptable']                                = 'tl_calendar_sub_events';
    $dca['config']['onload_callback'][]                     = ['huh.events.event_listener.data_container.content_listener', 'checkPermission'];
    $dca['list']['operations']['toggle']['button_callback'] = ['huh.events.event_listener.data_container.content_listener', 'toggleIcon'];
}
<?php

$dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

/**
 * Operations
 */
$dca['list']['operations']['subevents'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['subevents'],
    'href'  => 'table=tl_calendar_sub_events',
    'icon'  => 'bundles/heimrichhannotcontaoevents/img/icon-subevents.png'
];

/**
 * Fields
 */
$dca['fields']['startTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];
$dca['fields']['endTime']['load_callback']   = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];
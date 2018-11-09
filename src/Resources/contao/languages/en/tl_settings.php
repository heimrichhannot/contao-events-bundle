<?php

$lang = &$GLOBALS['TL_LANG']['tl_settings'];

/**
 * Fields
 */
$lang['subEventMode'][0] = 'Mode for sub events';
$lang['subEventMode'][1] = 'Choose here how sub events should be realized.';

/**
 * Legends
 */
$lang['events_bundle_legend'] = 'Events-Bundle';

/**
 * Reference
 */
$lang['reference']['eventsBundle'] = [
    \HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY => 'Realization as an entity "tl_calendar_sub_events"',
    \HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener::SUB_EVENT_MODE_RELATION => 'Realization using a parent-child-relation'
];
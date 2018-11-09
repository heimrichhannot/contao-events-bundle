<?php

$lang = &$GLOBALS['TL_LANG']['tl_settings'];

/**
 * Fields
 */
$lang['subEventMode'][0] = 'Modus für Unterveranstaltungen';
$lang['subEventMode'][1] = 'Wählen Sie hier aus, wie Unterveranstaltungen umgesetzt werden sollen.';

/**
 * Legends
 */
$lang['events_bundle_legend'] = 'Events-Bundle';

/**
 * Reference
 */
$lang['reference']['eventsBundle'] = [
    \HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener::SUB_EVENT_MODE_ENTITY => 'Umsetzung als Entität "tl_calendar_sub_events"',
    \HeimrichHannot\EventsBundle\EventListener\DataContainer\CalendarSubEventsListener::SUB_EVENT_MODE_RELATION => 'Umsetzung durch eine Parent-Child-Beziehung'
];
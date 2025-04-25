<?php

use HeimrichHannot\EventsBundle\Enum\SubEventMode;

$lang = &$GLOBALS['TL_LANG']['tl_settings'];

/**
 * Fields
 */
$lang['subEventMode'] = [];
$lang['subEventMode'][0]                         = 'Modus für Unterveranstaltungen';
$lang['subEventMode'][1]                         = 'Wählen Sie hier aus, wie Unterveranstaltungen umgesetzt werden sollen.';
$lang['skipCalendarEventCoordinateRetrieval'] = [];
$lang['skipCalendarEventCoordinateRetrieval'][0] = 'Koordinaten nicht automatisch berechnen';
$lang['skipCalendarEventCoordinateRetrieval'][1] = 'Wählen Sie diese Option, wenn die Koordinaten nicht durch Nutzung der Google GeoCode API automatisch berechnet werden sollen (dafür ist ein API-Schlüssel nötig!).';

/**
 * Legends
 */
$lang['events_bundle_legend'] = 'Events-Bundle';

/**
 * Reference
 */
$lang['reference']['eventsBundle'] = [
    SubEventMode::ENTITY->value   => 'Umsetzung als Entität "tl_calendar_sub_events"',
    SubEventMode::RELATION->value => 'Umsetzung durch eine Parent-Child-Beziehung',
];
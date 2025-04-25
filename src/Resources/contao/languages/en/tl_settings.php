<?php

use HeimrichHannot\EventsBundle\Enum\SubEventMode;

$lang = &$GLOBALS['TL_LANG']['tl_settings'];

/**
 * Fields
 */
$lang['subEventMode'] = [];
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
    SubEventMode::ENTITY->value   => 'Realization as an entity "tl_calendar_sub_events"',
    SubEventMode::RELATION->value => 'Realization using a parent-child-relation'
];
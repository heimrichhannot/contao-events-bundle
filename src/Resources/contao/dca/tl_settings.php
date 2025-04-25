<?php

use HeimrichHannot\EventsBundle\DataContainer\CalendarSubEventsContainer;

$dca = &$GLOBALS['TL_DCA']['tl_settings'];

/**
 * Palettes
 */
$dca['palettes']['default'] .= ';{events_bundle_legend},subEventMode,skipCalendarEventCoordinateRetrieval;';

/**
 * Fields
 */
$fields = [
    'subEventMode' => [
        'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['subEventMode'],
        'exclude'                 => true,
        'filter'                  => true,
        'inputType'               => 'select',
        'options' => [
            CalendarSubEventsContainer::SUB_EVENT_MODE_ENTITY,
            CalendarSubEventsContainer::SUB_EVENT_MODE_RELATION
        ],
        'reference' => &$GLOBALS['TL_LANG']['tl_settings']['reference']['eventsBundle'],
        'eval'                    => ['tl_class' => 'w50', 'includeBlankOption' => true],
    ],
    'skipCalendarEventCoordinateRetrieval' => [
        'label'                   => &$GLOBALS['TL_LANG']['tl_settings']['skipCalendarEventCoordinateRetrieval'],
        'exclude'                 => true,
        'inputType'               => 'checkbox',
        'eval'                    => ['tl_class' => 'w50'],
    ],
];

$dca['fields'] += $fields;

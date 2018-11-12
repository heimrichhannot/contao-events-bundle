<?php

$dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

System::getContainer()->get('huh.events.manager.events_manager')->initCalendarEventsDcaForSubEvents();

System::getContainer()->get('huh.utils.array')->insertInArrayByName(
    $dca['list']['operations'],
    'show', [
    'feature' => [
        'label'           => &$GLOBALS['TL_LANG']['tl_calendar_events']['feature'],
        'icon'            => 'featured.svg',
        'attributes'      => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleFeatured(this,%s)"',
        'button_callback' => ['huh.events.event_listener.data_container.calendar_events_listener', 'iconFeatured']
    ]
], 0
);

/**
 * Callbacks
 */
$dca['config']['onsubmit_callback'][] = ['huh.utils.dca', 'setDateAdded'];
$dca['config']['oncopy_callback'][]   = ['huh.utils.dca', 'setDateAddedOnCopy'];

/**
 * Palettes
 */
$dca['palettes']['default'] = str_replace('noComments', 'noComments,featured', $dca['palettes']['default']);
$dca['palettes']['default'] = str_replace('alias', 'alias,subTitle', $dca['palettes']['default']);

/**
 * Fields
 */
$fields = [
    'dateAdded' => [
        'label'   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
        'sorting' => true,
        'flag'    => 6,
        'eval'    => ['rgxp' => 'datim', 'doNotCopy' => true, 'noSubmissionField' => true],
        'sql'     => "int(10) unsigned NOT NULL default '0'",
    ],
    'subTitle' => [
        'label'                   => &$GLOBALS['TL_LANG']['tl_calendar_events']['subTitle'],
        'exclude'                 => true,
        'search'                  => true,
        'inputType'               => 'text',
        'eval'                    => ['maxlength' => 255, 'tl_class' => 'w50'],
        'sql'                     => "varchar(255) NOT NULL default ''"
    ],
    'featured'  => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['featured'],
        'exclude'   => true,
        'filter'    => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "char(1) NOT NULL default ''"
    ],
];

$dca['fields'] += $fields;

// avoid backend handling of timestamps for frontend
$dca['fields']['startTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];
$dca['fields']['endTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];
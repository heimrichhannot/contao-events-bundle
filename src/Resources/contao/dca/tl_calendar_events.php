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
$dca['palettes']['default'] = str_replace('alias', 'alias,shortTitle,subTitle', $dca['palettes']['default']);
$dca['palettes']['default'] = str_replace('location', '{location_legend},street,postal,city,coordinates,location,locationAdditional', $dca['palettes']['default']);
$dca['palettes']['default'] = str_replace('{location_legend}', '{contact_legend},website;{location_legend}', $dca['palettes']['default']);

/**
 * Fields
 */
$fields = [
    'dateAdded'          => [
        'label'   => &$GLOBALS['TL_LANG']['MSC']['dateAdded'],
        'sorting' => true,
        'flag'    => 6,
        'eval'    => ['rgxp' => 'datim', 'doNotCopy' => true, 'noSubmissionField' => true],
        'sql'     => "int(10) unsigned NOT NULL default '0'",
    ],
    'subTitle'           => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['subTitle'],
        'exclude'   => true,
        'search'    => true,
        'inputType' => 'text',
        'eval'      => ['maxlength' => 255, 'tl_class' => 'w50'],
        'sql'       => "varchar(255) NOT NULL default ''"
    ],
    'featured'           => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['featured'],
        'exclude'   => true,
        'filter'    => true,
        'inputType' => 'checkbox',
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "char(1) NOT NULL default ''"
    ],
    'shortTitle'         => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['shortTitle'],
        'inputType' => 'text',
        'exclude'   => true,
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "varchar(255) NOT NULL default ''",
    ],
    'locationAdditional' => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['locationAdditional'],
        'inputType' => 'text',
        'exclude'   => true,
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "varchar(255) NOT NULL default ''",
    ],
    'street'             => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['street'],
        'inputType' => 'text',
        'exclude'   => true,
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "varchar(128) NOT NULL default ''",
    ],
    'postal'             => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['postal'],
        'inputType' => 'text',
        'exclude'   => true,
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "varchar(64) NOT NULL default ''",
    ],
    'city'               => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['city'],
        'inputType' => 'text',
        'exclude'   => true,
        'eval'      => ['tl_class' => 'w50'],
        'sql'       => "varchar(128) NOT NULL default ''",
    ],
    'coordinates'        => [
        'label'         => &$GLOBALS['TL_LANG']['tl_calendar_events']['coordinates'],
        'inputType'     => 'text',
        'exclude'       => true,
        'eval'          => ['tl_class' => 'w50'],
        'save_callback' => [
            function ($value, \Contao\DataContainer $dc) {
                if (\Contao\Config::get('skipCalendarEventCoordinateRetrieval'))
                {
                    return $value;
                }

                return System::getContainer()->get('huh.utils.location')->computeCoordinatesInSaveCallback(
                    $value, $dc
                );
            }
        ],
        'sql'           => "varchar(64) NOT NULL default ''",
    ],
    'website'            => [
        'label'     => &$GLOBALS['TL_LANG']['tl_calendar_events']['website'],
        'inputType' => 'text',
        'exclude'   => true,
        'eval'      => ['tl_class' => 'w50', 'rgxp' => 'url', 'maxlength' => 255],
        'sql'       => "varchar(255) NOT NULL default ''",
    ],
];

$dca['fields'] += $fields;

// avoid backend handling of timestamps for frontend
$dca['fields']['startTime']['load_callback'] = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];
$dca['fields']['endTime']['load_callback']   = [['huh.events.event_listener.data_container.calendar_events_listener', 'loadTime']];

$dca['fields']['location']['eval']['tl_class'] = 'w50';
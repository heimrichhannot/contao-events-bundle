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
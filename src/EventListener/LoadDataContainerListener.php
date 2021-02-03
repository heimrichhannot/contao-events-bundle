<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener;

class LoadDataContainerListener
{
    public function __construct()
    {
    }

    /**
     * @Hook("loadDataContainer")
     */
    public function onLoadDataContainer(string $table): void
    {
        if ('tl_calendar_events' === $table) {
            if (!class_exists('HeimrichHannot\CalendarPlus\EventsPlus')) {
                $dca = &$GLOBALS['TL_DCA'][$table];
                $dca['palettes']['default'] = str_replace('noComments', 'noComments,featured', $dca['palettes']['default']);
                $dca['palettes']['default'] = str_replace('alias', 'alias,shortTitle,subTitle', $dca['palettes']['default']);
                $dca['palettes']['default'] = str_replace('location', '{location_legend},street,postal,city,coordinates,location,locationAdditional', $dca['palettes']['default']);
                $dca['palettes']['default'] = str_replace('{location_legend}', '{contact_legend},website;{location_legend}', $dca['palettes']['default']);
            }
        }
    }
}

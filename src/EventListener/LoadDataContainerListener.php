<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;

class LoadDataContainerListener
{
    /**
     * Hook("loadDataContainer")
     */
    public function onLoadDataContainer(string $table): void
    {
        if ('tl_calendar_events' === $table) {
            if (!class_exists('HeimrichHannot\CalendarPlus\EventsPlus')) {

                $dca = &$GLOBALS['TL_DCA'][$table];
                $dca['palettes']['default'] = str_replace(',location', '', $dca['palettes']['default']);
                $dca['palettes']['default'] = str_replace(',address', '', $dca['palettes']['default']);

                (new PaletteManipulator())
                    ->addField('featured', 'noComments', PaletteManipulator::POSITION_AFTER)
                        ->addField('shortTitle', 'alias', PaletteManipulator::POSITION_AFTER)
                        ->addField('subTitle', 'alias', PaletteManipulator::POSITION_AFTER)
                    ->addLegend('location_legend', 'details_legend')
                        ->addField('street', 'location_legend', PaletteManipulator::POSITION_APPEND)
                        ->addField('postal', 'location_legend', PaletteManipulator::POSITION_APPEND)
                        ->addField('city', 'location_legend', PaletteManipulator::POSITION_APPEND)
                        ->addField('coordinates', 'location_legend', PaletteManipulator::POSITION_APPEND)
                        ->addField('location', 'location_legend', PaletteManipulator::POSITION_APPEND)
                        ->addField('locationAdditional', 'location_legend', PaletteManipulator::POSITION_APPEND)
                    ->addLegend('contact_legend', 'location_legend')
                        ->addField('website', 'contact_legend', PaletteManipulator::POSITION_APPEND)
                    ->applyToPalette('default', 'tl_calendar_events');
            }
        }
    }
}

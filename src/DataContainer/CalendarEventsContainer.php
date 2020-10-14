<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\DataContainer;

use Contao\Config;
use Contao\DataContainer;
use Contao\System;

class CalendarEventsContainer
{
    public function modifyPalette(DataContainer $dc): void
    {
        if (null === ($news = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_calendar_events', $dc->id))) {
            return;
        }

        if (null === ($archive = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_calendar', $news->pid))) {
            return;
        }

        if (!$archive->addCustomEventsPalettes || !$archive->customEventsPalettes) {
            return;
        }

        if (!isset($GLOBALS['TL_DCA']['tl_calendar_events']['palettes'][$archive->customEventsPalettes])) {
            return;
        }

        $GLOBALS['TL_DCA']['tl_calendar_events']['palettes']['default'] = $GLOBALS['TL_DCA']['tl_calendar_events']['palettes'][$archive->customEventsPalettes];

        // HOOK: loadDataContainer must be triggerd after onload_callback, otherwise slick slider wont work anymore
        if (isset($GLOBALS['TL_HOOKS']['loadDataContainer']) && \is_array($GLOBALS['TL_HOOKS']['loadDataContainer'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadDataContainer'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}($dc->table);
            }
        }
    }

    public function onSaveCoordinates($value, ?DataContainer $dc): string
    {
        if (Config::get('skipCalendarEventCoordinateRetrieval')) {
            return $value;
        }

        return System::getContainer()->get('huh.utils.location')->computeCoordinatesInSaveCallback(
            $value, $dc
        );
    }
}

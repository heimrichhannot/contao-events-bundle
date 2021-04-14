<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\DataContainer;

use Contao\DataContainer;
use HeimrichHannot\UtilsBundle\Dca\DcaUtil;

class CalendarContainer
{
    /**
     * @var DcaUtil
     */
    protected $dcaUtil;

    public function __construct(DcaUtil $dcaUtil)
    {
        $this->dcaUtil = $dcaUtil;
    }

    public function getEventsPalettes(DataContainer $dc): array
    {
        $options = [];
        $this->dcaUtil->loadDc('tl_calendar_events');
        $palettes = $GLOBALS['TL_DCA']['tl_calendar_events']['palettes'];

        if (!\is_array($palettes)) {
            return $options;
        }

        foreach ($palettes as $name => $palette) {
            if (\in_array($name, ['__selector__', 'internal', 'external', 'default'], true)) {
                continue;
            }
            $options[$name] = $name;
        }

        return $options;
    }
}

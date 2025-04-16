<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\DataContainer;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\System;
use HeimrichHannot\EventsBundle\Model\CalendarEventsModel;
use HeimrichHannot\EventsBundle\Model\CalendarModel;
use HeimrichHannot\EventsBundle\Util\LocationUtil;
use HeimrichHannot\UtilsBundle\Util\Utils;

readonly class CalendarEventsContainer
{
    public const TABLE = 'tl_calendar_events';

    public function __construct(private LocationUtil $locationUtil, private Utils $utils) {}

    public function modifyPalette(DataContainer $dc): void
    {
        /** @var $news CalendarEventsModel */
        if (!$news = $this->utils->model()->findModelInstanceByPk(static::TABLE, $dc->id)) {
            return;
        }

        /** @var $archive CalendarModel */
        if (!$archive = $this->utils->model()->findModelInstanceByPk('tl_calendar', $news->pid)) {
            return;
        }

        if (!$archive->addCustomEventsPalettes || !$archive->customEventsPalettes) {
            return;
        }

        if (!$customPalettes = $GLOBALS['TL_DCA'][static::TABLE]['palettes'][$archive->customEventsPalettes] ?? null) {
            return;
        }

        $GLOBALS['TL_DCA'][static::TABLE]['palettes']['default'] = $customPalettes;

        // HOOK: loadDataContainer must be triggered after onload_callback, otherwise slick slider stops working
        if (isset($GLOBALS['TL_HOOKS']['loadDataContainer']) && \is_array($GLOBALS['TL_HOOKS']['loadDataContainer']))
        {
            foreach ($GLOBALS['TL_HOOKS']['loadDataContainer'] as $callback) {
                System::importStatic($callback[0])->{$callback[1]}($dc->table);
            }
        }
    }

    #[AsCallback(self::TABLE, 'fields.coordinates.save')]
    public function onSaveCoordinates($value, ?DataContainer $dc): string
    {
        if (Config::get('skipCalendarEventCoordinateRetrieval')) {
            return $value;
        }

        return $this->locationUtil->computeCoordinatesInSaveCallback($value, $dc);
    }
}

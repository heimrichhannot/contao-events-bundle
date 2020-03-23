<?php


namespace HeimrichHannot\EventsBundle\DataContainer;


use Contao\DataContainer;
use Contao\System;

class CalendarEventsContainer
{
    /**
     * @param DataContainer $dc
     */
    public function modifyPalette(DataContainer $dc): void
    {
        if(null === ($news = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_calendar_events', $dc->id))) {
            return;
        }

        if(null === ($archive = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_calendar', $news->pid))) {
            return;
        }

        if(!$archive->addCustomEventsPalettes || !$archive->customEventsPalettes) {
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
}
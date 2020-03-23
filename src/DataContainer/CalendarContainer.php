<?php


namespace HeimrichHannot\EventsBundle\DataContainer;


use Contao\Controller;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\System;

class CalendarContainer
{
    /**
     * @param DataContainer $dc
     * @return array
     */
    public function getEventsPalettes(DataContainer $dc): array
    {
        $options = [];
        System::getContainer()->get('contao.framework')->getAdapter(Controller::class)->loadDataContainer('tl_calendar_events');
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
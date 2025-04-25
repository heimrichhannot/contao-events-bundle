<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\EventListener\Contao;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use HeimrichHannot\EventsBundle\DataContainer\CalendarEventsContainer;
use HeimrichHannot\UtilsBundle\StaticUtil\SUtils;

#[AsHook('loadDataContainer')]
class LoadDataContainerListener
{
    public function __invoke(string $table): void
    {
        if ($table !== 'tl_calendar_events') {
            return;
        }

        $dca = &$GLOBALS['TL_DCA'][$table];

        if (!\class_exists('HeimrichHannot\CalendarPlus\EventsPlus'))
        {
            $dca['palettes']['default'] = str_replace(',location', '', $dca['palettes']['default']);
            $dca['palettes']['default'] = str_replace(',address', '', $dca['palettes']['default']);

            $paletteManipulator = new PaletteManipulator();

            // if contao version < 4.10 ... always false since version requirement went up to 4.13/5.0
            // if (!\class_exists(PackageUtil::class) || \version_compare(PackageUtil::getContaoVersion(), '4.10', '<')) {
            //     $paletteManipulator
            //         ->addField('featured', 'noComments', PaletteManipulator::POSITION_AFTER);
            // }

            $paletteManipulator
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

        if (!\array_key_exists('feature', $dca['list']['operations']))
        {
            SUtils::array()->insertAfterKey(
                $dca['list']['operations'],
                'show', [
                [
                    'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['feature'],
                    'icon' => 'featured.svg',
                    'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleFeatured(this,%s)"',
                    'button_callback' => [CalendarEventsContainer::class, 'iconFeatured'],
                ],
            ], 'feature'
            );
        }

        if (!\array_key_exists('featured', $dca['fields']))
        {
            $dca['fields']['featured'] = [
                'label' => &$GLOBALS['TL_LANG']['tl_calendar_events']['featured'],
                'exclude' => true,
                'filter' => true,
                'inputType' => 'checkbox',
                'eval' => ['tl_class' => 'w50 m12'],
                'sql' => "char(1) NOT NULL default ''",
            ];
        }
    }
}

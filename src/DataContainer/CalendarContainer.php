<?php

/*
 * @copyright Heimrich & Hannot GmbH, 2025
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;

readonly class CalendarContainer
{
    public const TABLE = 'tl_calendar';

    public function __construct(private ContaoFramework $framework) {}

    #[AsCallback(self::TABLE, 'fields.customEventsPalettes.options')]
    public function getEventsPalettes(DataContainer $dc): array
    {
        $table = 'tl_calendar_events';
        if (empty($GLOBALS['TL_DCA'][$table])) {
            /** @var Controller $controller */
            $controller = $this->framework->getAdapter(Controller::class);
            $controller->loadDataContainer($table);
        }

        if (!\is_array($palettes = $GLOBALS['TL_DCA'][$table]['palettes'] ?? null)) {
            return [];
        }

        $skip = \array_fill_keys(['__selector__', 'internal', 'external', 'default'], true);

        $options = [];

        foreach ($palettes as $name => $palette)
        {
            if (isset($skip[$name])) {
                continue;
            }
            $options[$name] = $name;
        }

        return $options;
    }
}

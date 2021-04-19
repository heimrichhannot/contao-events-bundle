<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

System::getContainer()->get(\HeimrichHannot\EventsBundle\Manager\EventsManager::class)->initCalendarSubEventsConfig();

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePreActions']['events-bundle'] = [
    \HeimrichHannot\EventsBundle\EventListener\Hook\HookListener::class,
    'executePreActions'
];

$GLOBALS['TL_HOOKS']['loadDataContainer']['huh_events'] = [
    \HeimrichHannot\EventsBundle\EventListener\LoadDataContainerListener::class,
    'onLoadDataContainer',
];
/*
 * Assets
 */
if (System::getContainer()->get(\HeimrichHannot\UtilsBundle\Container\ContainerUtil::class)->isBackend()) {
    $GLOBALS['TL_CSS']['events-bundle'] = 'bundles/heimrichhannotcontaoevents/css/contao-events-bundle.be.css|static';
}

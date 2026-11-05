<?php

/*
 * Copyright (c) 2020 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

\Contao\System::getContainer()->get(\HeimrichHannot\EventsBundle\Manager\EventsManager::class)->initCalendarSubEventsConfig();

/*
 * Hooks
 */

$GLOBALS['TL_HOOKS']['loadDataContainer']['huh_events'] = [
    \HeimrichHannot\EventsBundle\EventListener\LoadDataContainerListener::class,
    'onLoadDataContainer',
];
/*
 * Assets
 */
$request = \Contao\System::getContainer()->get('request_stack')->getCurrentRequest();
if ($request && \Contao\System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
    $GLOBALS['TL_CSS']['events-bundle'] = 'bundles/heimrichhannotcontaoevents/css/contao-events-bundle.be.css|static';
}

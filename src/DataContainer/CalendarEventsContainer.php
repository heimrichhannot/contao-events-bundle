<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\DataContainer;

use Contao\Config;
use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use HeimrichHannot\UtilsBundle\Util\ModelUtil;

class CalendarEventsContainer
{
    /**
     * @var ModelUtil
     */
    protected $modelUtil;
    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    public function __construct(ModelUtil $modelUtil, HttpClientInterface $httpClient, private Connection $connection)
    {
        $this->modelUtil = $modelUtil;
        $this->httpClient = $httpClient;
    }

    public function modifyPalette(DataContainer $dc): void
    {
        if (null === ($news = $this->modelUtil->findModelInstanceByPk('tl_calendar_events', (int) $dc->id))) {
            return;
        }

        if (null === ($archive = $this->modelUtil->findModelInstanceByPk('tl_calendar', $news->pid))) {
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
            return (string) $value;
        }

        if ($value || !$dc?->activeRecord) {
            return (string) $value;
        }

        $address = trim(implode(' ', [$dc->activeRecord->street, $dc->activeRecord->postal, $dc->activeRecord->city]));
        if ('' === $address) {
            return '';
        }

        $response = $this->httpClient->request('GET', 'https://maps.googleapis.com/maps/api/geocode/json', [
            'query' => ['address' => $address, 'key' => Config::get('utilsGoogleApiKey')],
        ])->toArray();
        if (isset($response['error_message'])) {
            throw new \RuntimeException($response['error_message']);
        }

        $location = $response['results'][0]['geometry']['location'] ?? null;

        return $location ? $location['lat'].','.$location['lng'] : '';
    }

    public function setDateAdded(DataContainer $dc): void
    {
        if ($dc->id) {
            $this->connection->executeStatement('UPDATE '.$dc->table.' SET dateAdded=? WHERE id=? AND dateAdded=0', [time(), $dc->id]);
        }
    }

    public function setDateAddedOnCopy(int $insertId, DataContainer $dc): void
    {
        $this->connection->update($dc->table, ['dateAdded' => time()], ['id' => $insertId]);
    }
}

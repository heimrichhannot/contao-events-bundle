<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\UtilsBundle\Location;

use Contao\Config;
use Contao\DataContainer;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Ported from HuH Utils Bundle v2.
 */
class LocationUtil
{
    public const GOOGLE_MAPS_GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&sensor=false';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Utils $utils,
    ) {}

    /**
     * Computes the coordinates from a given address. Supported array keys are:.
     *
     * - street
     * - postal
     * - city
     * - country
     */
    public function computeCoordinatesByArray(array $data): ?array
    {
        $criteria = [
            'street',
            'postal',
            'city',
            'state',
            'country',
        ];

        $sortedData = [];

        // keep the right order
        foreach ($criteria as $name) {
            if (isset($data[$name])) {
                $sortedData[] = $data[$name];
            }
        }

        return $this->computeCoordinatesByString(\implode(' ', $sortedData));
    }

    /**
     * Computes the coordinates from a given address. Supported array keys are:.
     *
     * - street
     * - postal
     * - city
     * - country
     */
    public function computeCoordinatesByString(string $address, ?string $apiKey = null): ?array
    {
        $url = \sprintf(static::GOOGLE_MAPS_GEOCODE_URL, \urlencode($address));

        if ($apiKey ??= Config::get('utilsGoogleApiKey'))
        {
            $url = $this->utils->url()->addQueryStringParameterToUrl('key=' . $apiKey, $url);
        }

        $response = $this->httpClient->request('GET', $url);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $data = $response->toArray(false);

        $location = $data['results'][0]['geometry']['location'] ?? [];

        if (!isset($location['lat']) || !isset($location['lng'])) {
            return null;
        }

        return ['lat' => $location['lat'], 'lng' => $location['lng']];
    }

    public function computeCoordinatesInSaveCallback($value, DataContainer $dc)
    {
        $data = [
            'street' => $dc->activeRecord->street,
            'postal' => $dc->activeRecord->postal,
            'city' => $dc->activeRecord->city,
        ];

        if ($value || empty(array_filter($data))) {
            return $value;
        }

        $result = $this->computeCoordinatesByArray([
            'street' => $dc->activeRecord->street,
            'postal' => $dc->activeRecord->postal,
            'city' => $dc->activeRecord->city,
        ]);

        if (!$result) {
            /*
            /** @var AttributeBagInterface $objSession * /
            $session = System::getContainer()->get('session')->getBag('contao_backend');

            if ($error = $session->get('utils.location.error')) {
                throw new \Exception($session->get('utils.location.error'));
            }
            */

            return '';
        }

        return $result['lat'].','.$result['lng'];
    }
}

<?php

/*
 * Copyright (c) 2021 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\EventsBundle\DataContainer;

use Contao\BackendUser;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use HeimrichHannot\EventsBundle\Manager\EventsManager;
use HeimrichHannot\EventsBundle\Model\CalendarEventsModel;
use HeimrichHannot\EventsBundle\Model\CalendarModel;
use HeimrichHannot\EventsBundle\Util\Helper;
use HeimrichHannot\EventsBundle\Util\LocationUtil;
use HeimrichHannot\UtilsBundle\Util\Utils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

readonly class CalendarEventsContainer
{
    public const TABLE = 'tl_calendar_events';

    public function __construct(
        private ContaoCsrfTokenManager $csrfTokenManager,
        private EventsManager          $eventsManager,
        private Helper                 $helper,
        private LocationUtil           $locationUtil,
        private ParameterBagInterface  $parameterBag,
        private RequestStack           $requestStack,
        private Utils                  $utils,
    ) {}


    /**
     * Set the timestamp to 1970-01-01 (see #26).
     */
    #[AsCallback(self::TABLE, 'fields.startTime.load')]
    #[AsCallback(self::TABLE, 'fields.endTime.load')]
    public function loadTime(int $value): int
    {
        if ($this->utils->container()->isFrontend()) {
            return $value;
        }

        return \strtotime('1970-01-01 ' . date('H:i:s', $value));
    }

    #[AsCallback(self::TABLE, 'config.onload')]
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

    public function listEvents(array $row): string
    {
        $date = Helper::getFormattedDateTime(
            startDate: $row['startDate'],
            endDate: $row['endDate'],
            addTime: $row['addTime'],
            startTime: $row['startTime'],
            endTime: $row['endTime']
        );

        return \sprintf(
            '<div class="tl_content_left">%s <span style="color:#999;padding-left:3px">[%s]</span>%s</div>',
            $row['title'], $date, $this->getSubEventsForBackendList($row['id'])
        );
    }

    public function getSubEventsForBackendList(int $id): string
    {
        // retrieve sub events
        if (!$events = $this->eventsManager->getSubEvents($id)) {
            return '';
        }

        $subEvents = '';

        while ($events->next())
        {
            $row = $events->row();
            $event = $events->current();

            $fmtDt = Helper::getFormattedDateTime(
                startDate: $event->startDate,
                endDate: $event->endDate,
                addTime: $event->addTime,
                startTime: $event->startTime,
                endTime: $event->endTime
            );

            $subEvents .= System::getContainer()->get('twig')->render(
                '@HeimrichHannotEvents/subevent_dc_default.twig',
                [
                    'row' => $row,
                    'date' => $fmtDt,
                    'operations' => $this->helper->generateDcOperationsButtons($row, 'tl_calendar_events', [], [
                        'skipOperations' => ['cut', 'copy'],
                    ]),
                ]
            );
        }

        return $subEvents;
    }

    public function checkForSubEvents(DataContainer $dc): void
    {
        if (!$dc->id || !\class_exists(CalendarEventsModel::class)) {
            return;
        }

        // /* @var CalendarEventsModel $adapter */
        // $adapter = $this->framework->getAdapter(CalendarEventsModel::class);

        if ($this->eventsManager->hasSubEvents((int) ($dc->id))) {
            $dca = &$GLOBALS['TL_DCA']['tl_calendar_events'];

            unset($dca['fields']['parentEvent']);
        }
    }

    /**
     * Return the "subevents" button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function iconSubEvents($row, $href, $label, $title, $icon, $attributes): string
    {
        $subEvents = $this->utils->model()->findModelInstancesBy('tl_calendar_sub_events', ['pid=?'], [$row['id']]);

        $icon = 'bundles/heimrichhannotevents/img/icon-subevents'.(null !== $subEvents ? '-existing' : '').'.png';

        $href .= '&id='.$row['id'];

        return '<a href="'.Controller::addToUrl($href, true, ['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Return the "create sub event" button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function iconCreateSubEvent($row, $href, $label, $title, $icon, $attributes): string
    {
        /* @var CalendarEventsModel $adapter */
        if ($row['parentEvent'] > 0) {
            return '';
        }

        $requestToken = $this->csrfTokenManager->getDefaultTokenValue();

        $href .= '&pid='.\Input::get('id').'&parentEvent='.$row['id'].'&rt=' . $requestToken;

        return '<a href="'.Controller::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Return the "feature/unfeature element" button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function iconFeatured($row, $href, $label, $title, $icon, $attributes): string
    {
        $user = BackendUser::getInstance();

        if (\strlen(Input::get('fid'))) {
            $this->toggleFeatured(Input::get('fid'), (1 == Input::get('state')), (@func_get_arg(12) ?: null));
            Controller::redirect(System::getReferer());
        }

        // Check permissions AFTER checking the fid, so hacking attempts are logged
        if (!$user->hasAccess('tl_calendar_events::featured', 'alexf')) {
            return '';
        }

        $href .= '&amp;fid='.$row['id'].'&amp;state='.($row['featured'] ? '' : 1);

        if (!$row['featured']) {
            $icon = 'featured_.svg';
        }

        return '<a href="'.Controller::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['featured'] ? 1 : 0).'"').'</a> ';
    }

    /**
     * Feature/unfeature a calendar event.
     *
     * @param int            $id
     * @param bool           $visible
     * @param ?DataContainer $dc
     *
     * @throws AccessDeniedException
     */
    public function toggleFeatured(int $id, bool $visible, ?DataContainer $dc = null): void
    {
        $user = BackendUser::getInstance();
        $db = Database::getInstance();

        // Check permissions to edit
        Input::setGet('id', $id);
        Input::setGet('act', 'feature');
        $this->checkPermission();

        // Check permissions to feature
        if (!$user->hasAccess('tl_calendar_events::featured', 'alexf')) {
            throw new AccessDeniedException('Not enough permissions to feature/unfeature calendar_event ID '.$id.'.');
        }

        $objVersions = new Versions('tl_calendar_events', $id);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['featured']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['featured']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $obj = System::importStatic($callback[0]);
                    $visible = $obj->{$callback[1]}($visible, $dc);
                } elseif (\is_callable($callback)) {
                    $visible = $callback($visible, $this);
                }
            }
        }

        // Update the database
        $db->prepare('UPDATE tl_calendar_events SET tstamp='.time().", featured='".($visible ? '1' : '')."' WHERE id=?")
            ->execute($id);

        $objVersions->create();
    }

    /**
     * Check permissions to edit table tl_calendar_events.
     *
     * @throws AccessDeniedException
     */
    public function checkPermission(): void
    {
        $user = BackendUser::getInstance();
        $db = Database::getInstance();
        $bundles = $this->parameterBag->get('kernel.bundles');

        // HOOK: comments extension required
        if (!isset($bundles['ContaoCommentsBundle'])) {
            $key = array_search('allowComments', $GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['headerFields']);
            unset($GLOBALS['TL_DCA']['tl_calendar_events']['list']['sorting']['headerFields'][$key]);
        }

        if ($user->isAdmin) {
            return;
        }

        // Set root IDs
        if (empty($user->calendars) || !\is_array($user->calendars)) {
            $root = [0];
        } else {
            $root = $user->calendars;
        }

        $id = \strlen(Input::get('id')) ? Input::get('id') : CURRENT_ID;

        // Check current action
        switch (Input::get('act')) {
            case 'paste':
                // Allow
                break;

            case 'create':
                if (!\strlen(Input::get('pid')) || !\in_array(Input::get('pid'), $root)) {
                    throw new AccessDeniedException('Not enough permissions to create events in calendar ID '.Input::get('pid').'.');
                }

                break;

            case 'cut':
            case 'copy':
                if (!\in_array(Input::get('pid'), $root)) {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' event ID '.$id.' to calendar ID '.Input::get('pid').'.');
                }
            // no break STATEMENT HERE

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
            case 'feature':
                $calendar = $db->prepare('SELECT pid FROM tl_calendar_events WHERE id=?')
                    ->limit(1)
                    ->execute($id);

                if ($calendar->numRows < 1) {
                    throw new AccessDeniedException('Invalid event ID '.$id.'.');
                }

                if (!\in_array($calendar->pid, $root)) {
                    throw new AccessDeniedException('Not enough permissions to '.Input::get('act').' event ID '.$id.' of calendar ID '.$calendar->pid.'.');
                }

                break;

            case 'select':
            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!\in_array($id, $root)) {
                    throw new AccessDeniedException('Not enough permissions to access calendar ID '.$id.'.');
                }

                $calendar = $db->prepare('SELECT id FROM tl_calendar_events WHERE pid=?')->execute($id);

                if ($calendar->numRows < 1) {
                    throw new AccessDeniedException('Invalid calendar ID '.$id.'.');
                }

                $session = $this->requestStack->getSession();
                $sessionData = $session->all();
                $sessionData['CURRENT']['IDS'] = \array_intersect((array) $sessionData['CURRENT']['IDS'], $calendar->fetchEach('id'));
                $session->replace($sessionData);

                break;

            default:
                if (\strlen(Input::get('act'))) {
                    throw new AccessDeniedException('Invalid command "'.Input::get('act').'".');
                } elseif (!\in_array($id, $root)) {
                    throw new AccessDeniedException('Not enough permissions to access calendar ID '.$id.'.');
                }

                break;
        }
    }
}

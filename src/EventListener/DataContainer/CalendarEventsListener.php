<?php

namespace HeimrichHannot\EventsBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Contao\System;
use HeimrichHannot\EventsBundle\Manager\EventsManager;
use HeimrichHannot\UtilsBundle\Util\ModelUtil;

class CalendarEventsListener
{
    private ?DataContainer $dataContainer = null;

    public function onLoad(DataContainer $dc): void
    {
        $this->dataContainer = $dc;
    }

    public function __construct(private EventsManager $eventsManager, private ModelUtil $modelUtil)
    {
    }

    public function listEvents(array $row): string
    {
        $formatter = System::importStatic('tl_calendar_events');
        $html = $formatter->listEvents($row);
        $events = $this->eventsManager->getSubEvents((int) $row['id']);

        if (null !== $events) {
            foreach ($events as $event) {
                $url = Backend::addToUrl('act=edit&id='.$event->id);
                $operations = '<a href="'.$url.'" title="'.StringUtil::specialchars($event->title).'">'.Image::getHtml('edit.svg', $event->title).'</a>';
                if ($this->dataContainer) {
                    // Reuse Contao's permission-aware operation renderer for nested rows.
                    $operations = (function (array $row): string {
                        $original = $GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'];
                        $GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'] = array_filter(
                            $original,
                            static fn ($value, $key) => !in_array(is_int($key) ? $value : $key, ['cut', 'copy'], true),
                            ARRAY_FILTER_USE_BOTH
                        );
                        try {
                            return $this->generateButtons($row, 'tl_calendar_events');
                        } finally {
                            $GLOBALS['TL_DCA']['tl_calendar_events']['list']['operations'] = $original;
                        }
                    })->call($this->dataContainer, $event->row());
                }
                $html .= '<div class="tl_content"><div class="tl_content_right">'.$operations.'</div>'.$formatter->listEvents($event->row()).'</div>';
            }
        }

        return $html;
    }

    public function checkForSubEvents(DataContainer $dc): void
    {
        if ($dc->id && $this->eventsManager->hasSubEvents((int) $dc->id)) {
            unset($GLOBALS['TL_DCA']['tl_calendar_events']['fields']['parentEvent']);
        }
    }

    public function loadTime($value)
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();
        if (!$request || !System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            return $value;
        }

        return strtotime('1970-01-01 '.date('H:i:s', (int) $value));
    }

    public function iconSubEvents($row, $href, $label, $title, $icon, $attributes)
    {
        $subEvents = $this->modelUtil->findModelInstancesBy('tl_calendar_sub_events', ['pid=?'], [$row['id']]);

        $icon = 'bundles/heimrichhannotcontaoevents/img/icon-subevents'.(null !== $subEvents ? '-existing' : '').'.png';

        $href .= '&id='.$row['id'];

        return '<a href="'.Backend::addToUrl($href, true, ['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
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
    public function iconCreateSubEvent($row, $href, $label, $title, $icon, $attributes)
    {
        /* @var CalendarEventsModel $adapter */
        if (($row['parentEvent'] ?? 0) > 0) {
            return '';
        }

        $href .= '&pid='.$row['pid'].'&parentEvent='.$row['id'];

        return '<a href="'.Backend::addToUrl($href).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

}

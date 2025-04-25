<?php

namespace HeimrichHannot\EventsBundle\Util;

use Contao\Controller;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\Image;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;

class Helper
{
    public const MONTHS_LONG = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];

    public const MONTHS_SHORT = [
        'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec',
    ];

    public static function getMonthTranslationMap(): array
    {
        System::loadLanguageFile('default');
        return \array_combine(static::MONTHS_LONG, $GLOBALS['TL_LANG']['MONTHS'] ?? static::MONTHS_LONG);
    }

    public static function getShortMonthTranslationMap(): array
    {
        System::loadLanguageFile('default');
        return \array_combine(static::MONTHS_SHORT, $GLOBALS['TL_LANG']['MONTHS_SHORT'] ?? static::MONTHS_SHORT);
    }

    public static function translateMonths(string $date): string
    {
        $map = \array_merge(
            static::getMonthTranslationMap(),
            static::getShortMonthTranslationMap(),
        );

        foreach ($map as $english => $translated)
        {
            $date = \str_replace($english, $translated, $date);
        }

        return $date;
    }

    public static function getFormattedDateTime($startDate, $endDate = 0, $addTime = false, $startTime = 0, $endTime = 0, array $options = []): ?string
    {
        $res = static::getFormattedDateTimeInner($startDate, $endDate, $addTime, $startTime, $endTime, $options);

        if ($options['translateMonths'] ?? false)
        {
            $res = static::translateMonths($res);
        }

        return $res;
    }

    private static function getFormattedDateTimeInner($startDate, $endDate = 0, $addTime = false, $startTime = 0, $endTime = 0, array $options = []): ?string
    {
        $dateFormat = $options['dateFormat'] ?? \Date::getNumericDateFormat();
        $datimFormat = $options['datimFormat'] ?? \Date::getNumericDatimFormat();
        $timeFormat = $options['timeFormat'] ?? \Date::getNumericTimeFormat();
        $separator = $options['separator'] ?? ' – ';

        $startDateFormatted = \date($dateFormat, $startDate);
        $endDateFormatted = \date($dateFormat, $endDate);

        if (!$addTime)
        {
            if (!$endDate || $startDateFormatted === $endDateFormatted) {
                return $startDateFormatted;
            }

            return $startDateFormatted.$separator.$endDateFormatted;
        }

        if (!$endDate || $startDateFormatted === $endDateFormatted)
        {
            $startTimeFormatted = \date($timeFormat, $startTime);
            $endTimeFormatted = \date($timeFormat, $endTime);

            $result = $startDateFormatted.' '.$startTimeFormatted;

            if ($startTimeFormatted !== $endTimeFormatted) {
                $result .= $separator.$endTimeFormatted;
            }

            return $result;
        }

        $startDateTimeFormatted = \date($datimFormat, $startTime);
        $endDateTimeFormatted = \date($datimFormat, $endTime);

        if (!$endTime || $startDateTimeFormatted === $endDateTimeFormatted) {
            return $startDateTimeFormatted;
        }

        return $startDateTimeFormatted.$separator.$endDateTimeFormatted;
    }

    public function __construct(
        private readonly ContaoCsrfTokenManager $csrfTokenManager,
    ) {}

    public function generateDcOperationsButtons($row, $table, $rootIds = [], $options = []): string
    {
        if (Input::get('act') === 'select')
            // Edit multiple
        {
            return '<input type="checkbox" name="IDS[]" id="ids_'.$row['id'].'" class="tl_tree_checkbox" value="'.$row['id'].'">';
        }

        return $this->doGenerateDcOperationsButtons($row, $table, $rootIds, false, null, $options);
    }

    public function doGenerateDcOperationsButtons(
        array  $row,
        string $table,
        array  $rootIds = [],
        bool   $circularReference = false,
        ?array $childIds = null,
        array  $options = [],
    ): string {
        if (!$operations = $GLOBALS['TL_DCA'][$table]['list']['operations'] ?? null) {
            return '';
        }

        $compound = '';

        $allowedOperations = $options['operations']
            ?? \array_diff(\array_keys($operations), $options['skipOperations'] ?? []);
        $operations = \array_intersect_key($operations, \array_flip($allowedOperations));

        foreach ($operations as $k => $v)
        {
            /*if (!\in_array($k, $allowedOps)) {
                continue;
            }*/

            $v = \is_array($v) ? $v : [$v];

            $id = $row['id'];
            $htmlId = StringUtil::specialchars(\rawurldecode($row['id']));

            $icon = $v['icon'];

            $label = $v['label'] ?? null;
            $title = $v['label'] ?? null;

            $href = $v['href'] ?? null;

            if (\is_array($label)) {
                $label = $label[0] ?? null;
                $title = $label[1] ?? '';
            }

            $label ??= $k;
            $title = \sprintf($title, $htmlId);

            $attributes = $v['attributes'] ?? null;
            $attributes .= ' ' . \ltrim(\sprintf($attributes, $htmlId, $htmlId));

            \parse_str(StringUtil::decodeEntities($href ?: ''), $params);

            $stateField = $params['field'] ?? 'published';
            $state = (bool) $row[$stateField];
            if ($v['reverse'] ?? false) {
                $state = !$state;
            }

            $iconFilename = \pathinfo($icon, \PATHINFO_FILENAME);
            $iconExtension = \pathinfo($icon, \PATHINFO_EXTENSION);
            $_icon = \sprintf('%s_.%s', $iconFilename, $iconExtension);

            if ('visible.svg' === $icon) {
                $_icon = 'invisible.svg';
            } elseif (\str_contains($icon, '/')) {
                $_icon = \dirname($icon) . '/' . $_icon;
            }

            $isToggle = $k === 'toggle' || $k === 'feature';

            if ($isToggle && !\str_contains($attributes, 'onclick'))
            {
                $attributes .= \sprintf(
                ' onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,%s)"',
                    $state ? 'true' : 'false'
                );
            }

            // Add the key as CSS class
            if (\str_contains($attributes, 'class="')) {
                $attributes = \str_replace('class="', "class=\"$k ", $attributes);
            } else {
                $attributes .= " class=\"$k\"";
            }

            // Call a custom function instead of using the default button
            if ($buttonCallback = $v['button_callback'] ?? null)
            {
                if (\is_array($buttonCallback))
                {
                    $container = System::importStatic($buttonCallback[0]);
                    $buttonCallback = $container->{$buttonCallback[1]}(...);
                }

                if (\is_callable($buttonCallback))
                {
                    $compound .= $buttonCallback($row, $href, $label, $title, $icon, $attributes, $table, $rootIds, $childIds, $circularReference, null, null, $this);
                    continue;
                }
            }

            // Generate all buttons except "move up" and "move down" buttons
            if ($k === 'move') {
                continue;
            }

            $csrfToken = $this->csrfTokenManager->getDefaultTokenValue();
            // $href . '&amp;id=' . $id . '&amp;rt=' . $csrfToken
            $idHref = Controller::addToUrl(\implode('&amp;', [$href, 'id=' . $id, 'rt=' . $csrfToken]));

            if ($k === 'show')
            {
                $compound .= \sprintf(
                    ' <a href="%s" title="%s" onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false;" %s>%s</a> ',
                    Controller::addToUrl($idHref . '&amp;popup=1'),
                    StringUtil::specialchars($title),
                    StringUtil::specialchars(\str_replace("'","\\'", \sprintf($GLOBALS['TL_LANG'][$table]['show'][1] ?? $title, $id))),
                    $attributes,
                    Image::getHtml($icon, $label),
                );
                continue;
            }

            $iconHtml = $isToggle
                ? Image::getHtml($state ? $icon : $_icon, $label, \sprintf(
                    'data-icon="%s" data-icon-disabled="%s" data-icon-state="%s"',
                    Image::getPath($icon), Image::getPath($_icon), (int) $state
                ))
                : Image::getHtml($icon, $label);

            $compound .= \sprintf(
                ' <a href="%s" title="%s" %s>%s</a> ',
                $idHref, StringUtil::specialchars($title), $attributes, $iconHtml
            );
        }

        return \trim(\preg_replace('/\s+/', ' ', $compound));
    }
}
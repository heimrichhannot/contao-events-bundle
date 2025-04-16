<?php

namespace HeimrichHannot\EventsBundle\Util;

use Contao\Controller;
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

    public static function generateDcOperationsButtons($row, $table, $rootIds = [], $options = []): string
    {
        // Edit multiple
        if ('select' == Input::get('act'))
        {
            return '<input type="checkbox" name="IDS[]" id="ids_'.$row['id'].'" class="tl_tree_checkbox" value="'.$row['id'].'">';
        }

        return static::doGenerateDcOperationsButtons($row, $table, $rootIds, false, null, $options);
    }

    public static function doGenerateDcOperationsButtons(
        $arrRow,
        $strTable,
        $arrRootIds = [],
        $blnCircularReference = false,
        $arrChildRecordIds = null,
        $options = [],
    ): string {
        if (!$dcOps = $GLOBALS['TL_DCA'][$strTable]['list']['operations'] ?? null) {
            return '';
        }

        $return = '';

        $allowedOps = $options['operations'] ?? \array_diff(\array_keys($dcOps), $options['skipOperations'] ?? []);

        $dcOps = \array_intersect_key($dcOps, \array_flip($allowedOps));

        foreach ($dcOps as $k => $v)
        {
            /*if (!\in_array($k, $allowedOps)) {
                continue;
            }*/

            $v = \is_array($v) ? $v : [$v];
            $id = StringUtil::specialchars(\rawurldecode($arrRow['id']));

            $label = $v['label'] ?? null;
            $title = $v['label'] ?? null;

            if (\is_array($label)) {
                $label = $label[0] ?? '';
                $title = $label[1] ?? '';
            }

            $label ??= $k;
            $title = \sprintf(isset($v['label']) ? (\is_array($v['label']) ? $v['label'][1] : $v['label']) : $k, $id);

            $attributes = ('' != $v['attributes']) ? ' '. \ltrim(sprintf($v['attributes'], $id, $id)) : '';

            \parse_str(StringUtil::decodeEntities($v['href'] ?? ''), $params);

            if (\in_array($k, ['toggle', 'feature']))
            {
                $state = $arrRow[$params['field']] ? 1 : 0;

                if ($v['reverse'] ?? false) {
                    $state = $arrRow[$params['field']] ? 0 : 1;
                }

                $icon = $v['icon'];
                $_icon = pathinfo($v['icon'], \PATHINFO_FILENAME).'_.'.pathinfo($v['icon'], \PATHINFO_EXTENSION);

                if (\str_contains($v['icon'], '/')) {
                    $_icon = \dirname($v['icon']).'/'.$_icon;
                }

                if ('visible.svg' == $icon) {
                    $_icon = 'invisible.svg';
                }

                if (!\str_contains($attributes, 'onclick')) {
                    $attributes = sprintf(
                        'onclick="Backend.getScrollOffset();return AjaxRequest.toggleField(this,'.($state ? 'true' : 'false').')"',
                        $id, $id
                    );
                }
            }

            // Add the key as CSS class
            if (\str_contains($attributes, 'class="')) {
                $attributes = str_replace('class="', 'class="'.$k.' ', $attributes);
            } else {
                $attributes = ' class="'.$k.'"'.$attributes;
            }

            // Call a custom function instead of using the default button
            if (\is_array($v['button_callback'])) {
                $callback = System::importStatic($v['button_callback'][0]);
                $return .= $callback->{$v['button_callback'][1]}($arrRow, $v['href'], $label, $title, $v['icon'], $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, null, null, $this);

                continue;
            } elseif (\is_callable($v['button_callback'])) {
                $return .= $v['button_callback']($arrRow, $v['href'], $label, $title, $v['icon'], $attributes, $strTable, $arrRootIds, $arrChildRecordIds, $blnCircularReference, null, null, $this);

                continue;
            }

            // Generate all buttons except "move up" and "move down" buttons
            if ($k === 'move' || $v === 'move') {
                continue;
            }

            // todo: ready

            if ('show' == $k)
            {
                $return .= \sprintf('<a href="%s" title="%s" onclick="Backend.openModalIframe({\'title\':\'%s\',\'url\':this.href});return false;" %s>%s</a>',
                    Controller::addToUrl($v['href'] . '&amp;id=' . $arrRow['id'] . '&amp;popup=1&amp;rt=' . \Contao\RequestToken::get()),
                    StringUtil::specialchars($title),
                    StringUtil::specialchars(
                        \str_replace("'","\\'", sprintf($GLOBALS['TL_LANG'][$strTable]['show'][1], $arrRow['id']))
                    ),
                    $attributes,
                    Image::getHtml($v['icon'], $label),
                );
                $return .= '<a  onclick="Backend.openModalIframe({\'title\':\'\',\'url\':this.href});return false"' . $attributes . '>' .  . '</a> ';
                continue;
            }

            $href = Controller::addToUrl($v['href'].'&amp;id='.$arrRow['id'].(Input::get('nb') ? '&amp;nc=1' : '')).'&amp;rt='.\Contao\RequestToken::get();

            if (\in_array($k, ['toggle', 'feature'])) {
                $icon = Image::getHtml($state ? $icon : $_icon, $label, 'data-icon="'.Image::getPath($icon).'" data-icon-disabled="'.Image::getPath($_icon).'" data-state="'.$state.'"');
            } else {
                $icon = Image::getHtml($v['icon'], $label);
            }

            $return .= '<a href="'.$href.'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.$icon.'</a> ';
        }

        return trim($return);
    }
}
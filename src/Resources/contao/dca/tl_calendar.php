<?php

$dca = &$GLOBALS['TL_DCA']['tl_calendar'];

$dca['palettes']['__selector__'][] = 'addCustomEventsPalettes';

$dca['palettes']['default'] = str_replace('jumpTo;','jumpTo;{palettes_legend},addCustomEventsPalettes;', $dca['palettes']['default']);

$dca['subpalettes']['addCustomEventsPalettes'] = 'customEventsPalettes';

$dca['fields']['addCustomEventsPalettes'] = [
    'inputType' => 'checkbox',
    'exclude' => true,
    'sql' => "char(1) NOT NULL default ''",
    'eval' => ['submitOnChange' => true],
];

$dca['fields']['customEventsPalettes'] = [
    'inputType' => 'select',
    'eval' => ['tl_class' => 'w50 clr', 'includeBlankOption' => true],
    'exclude' => true,
    'sql' => "varchar(50) NOT NULL default ''",
];
<?php

namespace HeimrichHannot\EventsBundle\Util;

use Contao\DataContainer;
use Contao\Model;

class DcaHelper
{
    public static function activeRecord(?DataContainer $dc): ?Model
    {
        if (!$dc || !($id = $dc->id ?? null)) {
            return null;
        }

        $modelClass = Model::getClassFromTable($dc->table);
        if (!\class_exists($modelClass)) {
            return null;
        }

        if (!($record = $modelClass::findByPk($id))) {
            return null;
        }

        return $record;
    }
}
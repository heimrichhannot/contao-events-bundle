<?php

namespace HeimrichHannot\EventsBundle\Enum;

use Contao\Config;

enum SubEventMode: string
{
    case ENTITY = 'entity';
    case RELATION = 'relation';
    case NONE = 'none';

    public static function fromConfig(): self
    {
        return self::tryFrom(Config::get('subEventMode')) ?: self::NONE;
    }

    public static function is(SubEventMode $subEventMode): bool
    {
        return self::fromConfig() === $subEventMode;
    }
}
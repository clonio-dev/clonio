<?php

declare(strict_types=1);

namespace App\Data;

enum KeyRemappingStrategyEnum: string
{
    case RandomInteger = 'random_integer';
    case NewUuid = 'new_uuid';
}

<?php

declare(strict_types=1);

namespace App\Data;

enum ColumnMutationStrategyEnum: string
{
    case FAKE = 'fake';
    case MASK = 'mask';
    case HASH = 'hash';
    case NULL = 'null';
    case STATIC = 'static';
}

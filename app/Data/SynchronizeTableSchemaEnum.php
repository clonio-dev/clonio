<?php

declare(strict_types=1);

namespace App\Data;

enum SynchronizeTableSchemaEnum: string
{
    case DROP_CREATE = 'drop_create';
    case TRUNCATE = 'truncate';
}

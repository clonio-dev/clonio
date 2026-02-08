<?php

declare(strict_types=1);

namespace App\Data;

enum RowSelectionStrategyEnum: string
{
    case FullTable = 'full_table';
    case FirstX = 'first_x';
    case LastX = 'last_x';
}

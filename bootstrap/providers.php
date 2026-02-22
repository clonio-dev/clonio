<?php

declare(strict_types=1);

$providers = [
    App\Providers\AppServiceProvider::class,
];

if (env('APP_MODE', 'application') !== 'marketing') {
    $providers[] = App\Providers\FortifyServiceProvider::class;
}

return $providers;

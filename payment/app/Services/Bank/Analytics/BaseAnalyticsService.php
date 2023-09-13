<?php

namespace App\Services\Bank\Analytics;

use App\Services\Bank\BaseBankService;

trait BaseAnalyticsService
{

    use BaseBankService;

    protected function analyticsUrl(string $path): string
    {
        return $this->getUrl("/analytics$path");
    }

}

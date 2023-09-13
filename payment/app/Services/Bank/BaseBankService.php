<?php

namespace App\Services\Bank;

use App\Services\BaseService;

trait BaseBankService
{

    use BaseService;

    private string $host;

    private function __construct(){
        $this->host = preg_replace('/\/&/', '', config('microservices.bank.host'));
    }

    protected function getUrl(string $path): string
    {
        return $this->host . $path;
    }

}

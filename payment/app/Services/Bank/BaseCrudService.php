<?php

namespace App\Services\Bank;

trait BaseCrudService
{

    use BaseBankService;

    protected function crudUrl(string $path): string
    {
        return $this->getUrl("/crud$path");
    }
}

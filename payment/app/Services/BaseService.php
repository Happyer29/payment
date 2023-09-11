<?php

namespace App\Services;

trait BaseService
{
    private static self $INSTANCE;
    public static function instance(): self
    {
        if(!isset(self::$INSTANCE) or !self::$INSTANCE){
            self::$INSTANCE = new self();
        }
        return self::$INSTANCE;
    }
}

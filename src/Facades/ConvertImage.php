<?php

namespace Daaner\ConvertImage\Facades;

use Illuminate\Support\Facades\Facade;


class ConvertImage extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'convert';
    }
}

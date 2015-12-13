<?php

namespace App\Handler;

class HomeHandler
{
    public function get()
    {
        var_dump('Hello');
        \Log::info("hello", 'test.log');
    }
}

<?php

class TNewUser extends Test
{
    function __construct()
    {
        $this->name = 'New User';
    }

    public function Run()
    {
        // TODO: something
        $this->finished = true;
    }

    public function OkForProduction()
    {
        return false;
    }
}

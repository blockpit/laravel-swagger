<?php

namespace Mtrajano\LaravelSwagger\Interfaces;

interface Documentable
{
    /**
     * Returns swagger response documentation
     * @return array
     */
    public static function docResponses();
}

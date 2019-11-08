<?php declare(strict_types=1);


namespace Mtrajano\LaravelSwagger\Annotations;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Resource
{
    /**
     * @var mixed
     * @Required
     */
    public $resource;


}

<?php declare(strict_types=1);


namespace blockpit\LaravelSwagger\Annotations;

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

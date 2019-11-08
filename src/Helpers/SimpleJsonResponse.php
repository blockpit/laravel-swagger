<?php

namespace Mtrajano\LaravelSwagger\Helpers;

class SimpleJsonResponse implements \JsonSerializable
{


    /**
     * @var array $items
     */
    private $items;
    /**
     * @var string
     */
    private $description;

    /**
     * SimpleJsonResponse constructor.
     * @param array $items
     * @param string $description
     */
    public function __construct(array $items, string $description = "no description provided")
    {
        $this->items = $items;
        $this->description = $description;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $data = [
            'description' => $this->description,
            'examples' => [
                "application/json" => $this->items
            ]
        ];

        return $data;
    }

}

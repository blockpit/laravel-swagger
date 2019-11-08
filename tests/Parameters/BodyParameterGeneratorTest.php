<?php

namespace blockpit\LaravelSwagger\Tests\Parameters;

use blockpit\LaravelSwagger\Tests\TestCase;
use blockpit\LaravelSwagger\Parameters\BodyParameterGenerator;

class BodyParameterGeneratorTest extends TestCase
{
    public function testStructure()
    {
        $bodyParameters = $this->getBodyParameters([]);

        $this->assertArrayHasKey('in', $bodyParameters);
        $this->assertArrayHasKey('name', $bodyParameters);
        $this->assertArrayHasKey('schema', $bodyParameters);
        $this->assertArraySubset(['type' => 'object'], $bodyParameters['schema']);
    }

    public function testRequiredParameters()
    {
        $bodyParameters = $this->getBodyParameters([
            'id'            => 'integer|required',
            'email'         => 'email|required',
            'address'       => 'string|required',
            'dob'           => 'date|required',
            'picture'       => 'file',
            'is_validated'  => 'boolean',
            'score'         => 'numeric',
        ]);

        $this->assertEquals([
            'id',
            'email',
            'address',
            'dob',
        ], $bodyParameters['schema']['required']);

        return $bodyParameters;
    }

    /**
     * @depends testRequiredParameters
     */
    public function testDataTypes($bodyParameters)
    {
        $this->assertEquals([
            'id'            => ['type' => 'integer'],
            'email'         => ['type' => 'string'],
            'address'       => ['type' => 'string'],
            'dob'           => ['type' => 'string'],
            'picture'       => ['type' => 'string'],
            'is_validated'  => ['type' => 'boolean'],
            'score'         => ['type' => 'number'],
        ], $bodyParameters['schema']['properties']);
    }

    public function testNoRequiredParameters()
    {
        $bodyParameters = $this->getBodyParameters([]);

        $this->assertArrayNotHasKey('required', $bodyParameters['schema']);
    }

    public function testEnumInBody()
    {
        $bodyParameters = $this->getBodyParameters([
            'account_type' => 'integer|in:1,2|in_array:foo',
        ]);

        $this->assertEquals([
            'account_type' => [
                'type' => 'integer',
                'enum' => [1,2],
            ]
        ], $bodyParameters['schema']['properties']);
    }

    public function testArraySyntax()
    {
        $bodyParameters = $this->getBodyParameters([
            'matrix' => 'array',
            'matrix.*' => 'array',
            'matrix.*.*' => 'integer',
        ]);

        $this->assertEquals([
            'matrix' => [
                'type' => 'array',
                'items' => [
                    [
                        'type' => 'array',
                        'items' => [
                            [
                                'type' => 'integer'
                            ]
                        ]
                    ]
                ]
            ]
        ], $bodyParameters['schema']['properties']);
    }

    public function testObjectInArraySyntax()
    {
        $bodyParameters = $this->getBodyParameters([
            'points' => 'array',
            'points.*.x' => 'numeric',
            'points.*.y' => 'numeric',
        ]);

        $this->assertEquals([
            'points' => [
                'type' => 'array',
                'items' => [
                    [
                        'type' => 'object',
                        'properties' => [
                            'x' => [
                                'type' => 'number'
                            ],
                            'y' => [
                                'type' => 'number'
                            ],
                        ]
                    ]
                ]
            ]
        ], $bodyParameters['schema']['properties']);
    }

    public function testSingleObjectSyntax()
    {
        $bodyParameters = $this->getBodyParameters([
            'point' => '',
            'point.x' => 'numeric',
            'point.y' => 'numeric',
        ]);

        $this->assertEquals([
            'point' => [
                'type' => 'object',
                'properties' => [
                    'x' => [
                        'type' => 'number'
                    ],
                    'y' => [
                        'type' => 'number'
                    ],
                ]
            ]
        ], $bodyParameters['schema']['properties']);
    }

    private function getBodyParameters(array $rules)
    {
        $bodyParameters = (new BodyParameterGenerator($rules))->getParameters();

        return current($bodyParameters);
    }
}
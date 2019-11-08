<?php

namespace blockpit\LaravelSwagger\Tests\Parameters;

use Illuminate\Validation\Rule;
use blockpit\LaravelSwagger\Tests\TestCase;
use blockpit\LaravelSwagger\Parameters\QueryParameterGenerator;

class QueryParameterGeneratorTest extends TestCase
{
    public function testRequiredParameter()
    {
        $queryParameters = $this->getQueryParameters([
            'id' => 'integer|required',
        ]);

        $this->assertArraySubset([
            'in' => 'query',
            'type' => 'integer',
            'name' => 'id',
            'required' => true,
        ], $queryParameters[0]);
    }

    public function testRulesAsArray()
    {
        $queryParameters = $this->getQueryParameters([
            'id' => ['integer', 'required'],
        ]);

        $this->assertArraySubset([
            'in' => 'query',
            'type' => 'integer',
            'name' => 'id',
            'required' => true,
        ], $queryParameters[0]);
    }

    public function testOptionalParameter()
    {
        $queryParameters = $this->getQueryParameters([
            'email' => 'email',
        ]);

        $this->assertArraySubset([
            'name' => 'email',
            'type' => 'string',
            'required' => false,
        ], $queryParameters[0]);
    }

    public function testEnumInQuery()
    {
        $queryParameters = $this->getQueryParameters([
            'account_type' => 'integer|in:1,2|in_array:foo',
        ]);

        $this->assertArraySubset([
            'name' => 'account_type',
            'type' => 'integer',
            'enum' => [1,2],
        ], $queryParameters[0]);
    }

    public function testEnumRuleObjet()
    {
        $queryParameters = $this->getQueryParameters([
            'account_type' => [
                'integer',
                Rule::in(1,2),
                'in_array:foo'
            ],
        ]);

        $this->assertArraySubset([
            'name' => 'account_type',
            'type' => 'integer',
            'enum' => ["\"1\"","\"2\""], //using Rule::in parameters are cast to string
        ], $queryParameters[0]);
    }

    public function testArrayTypeDefaultsToString()
    {
        $queryParameters = $this->getQueryParameters([
            'values' => 'array',
        ]);

        $this->assertArraySubset([
            'name' => 'values',
            'type' => 'array',
            'required' => false,
            'items' => [
                'type' => 'string',
            ],
        ], $queryParameters[0]);
    }

    public function testArrayValidationSyntax()
    {
        $queryParameters = $this->getQueryParameters([
            'values.*' => 'integer',
        ]);

        $this->assertArraySubset([
            'name' => 'values',
            'type' => 'array',
            'required' => false,
            'items' => [
                'type' => 'integer',
            ],
        ], $queryParameters[0]);
    }

    public function testArrayValidationSyntaxWithRequiredArray()
    {
        $queryParameters = $this->getQueryParameters([
            'values.*' => 'integer',
            'values' => 'required',
        ]);

        $this->assertArraySubset([
            'name' => 'values',
            'type' => 'array',
            'required' => true,
            'items' => [
                'type' => 'integer',
            ],
        ], $queryParameters[0]);
    }

    private function getQueryParameters(array $rules)
    {
        return (new QueryParameterGenerator($rules))->getParameters();
    }
}
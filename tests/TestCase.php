<?php

namespace blockpit\LaravelSwagger\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return ['blockpit\LaravelSwagger\SwaggerServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['router']->get('/users', 'blockpit\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@index');
        $app['router']->get('/users/{id}', 'blockpit\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@show');
        $app['router']->post('/users', 'blockpit\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@store');
        $app['router']->get('/users/details', 'blockpit\\LaravelSwagger\\Tests\\Stubs\\Controllers\\UserController@details');
        $app['router']->get('/users/ping', function() {
            return 'pong';
        });
        $app['router']->get('/api', 'blockpit\\LaravelSwagger\\Tests\\Stubs\\Controllers\\ApiController@index');
        $app['router']->put('/api/store', 'blockpit\\LaravelSwagger\\Tests\\Stubs\\Controllers\\ApiController@store');
    }
}

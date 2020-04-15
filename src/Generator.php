<?php

namespace blockpit\LaravelSwagger;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\DocBlockFactory;
use ReflectionClass;
use ReflectionMethod;
use zpt\anno\Annotations;

class Generator
{
    protected $config;

    protected $routeFilter;

    protected $docs;

    protected $uri;

    protected $originalUri;

    protected $method;

    protected $action;

    protected $tags;

    protected $scopes;

    public function __construct($config, $routeFilter = null)
    {
        $this->config = $config;
        $this->routeFilter = $routeFilter;
        $this->docParser = DocBlockFactory::createInstance();
        $this->tags = collect();
        $this->scopes = collect();
    }

    public function generate()
    {
        $this->docs = $this->getBaseInfo();

        foreach ($this->getAppRoutes() as $route) {
            $this->originalUri = $uri = $this->getRouteUri($route);
            $this->uri = strip_optional_char($uri);

            if ($this->routeFilter && !preg_match('/^' . preg_quote($this->routeFilter, '/') . '/', $this->uri)) {
                continue;
            }

            $this->action = $route->getAction()['uses'];

            $methods = $route->methods();
            if (!isset($this->docs['paths'][$this->uri])) {
                $this->docs['paths'][$this->uri] = [];
            }

            foreach ($methods as $method) {
                $this->method = strtolower($method);
                if (in_array($this->method, $this->config['ignoredMethods']) || gettype($this->action) != 'string') {
                    continue;
                }
                $this->generatePath();
            }
        }

        return ['docs' => $this->docs, 'tags' =>  $this->tags, 'scopes' => $this->scopes];
    }

    protected function getBaseInfo()
    {
        $baseInfo = [
            'swagger' => '2.0',
            'info' => [
                'title' => $this->config['title'],
                'description' => $this->config['description'],
                'version' => $this->config['appVersion'],
            ],
            'host' => $this->config['host'],
            'basePath' => $this->config['basePath'],
        ];

        if (!empty($this->config['schemes'])) {
            $baseInfo['schemes'] = $this->config['schemes'];
        }

        if (!empty($this->config['consumes'])) {
            $baseInfo['consumes'] = $this->config['consumes'];
        }

        if (!empty($this->config['produces'])) {
            $baseInfo['produces'] = $this->config['produces'];
        }

        $baseInfo['paths'] = [];

        return $baseInfo;
    }

    protected function getAppRoutes()
    {
        return app('router')->getRoutes();
    }

    protected function getRouteUri(Route $route)
    {
        $uri = $route->uri();

        if (!Str::startsWith($uri, '/')) {
            $uri = '/' . $uri;
        }

        return $uri;
    }

    protected function generatePath()
    {
        $docResponses = null;
        $actionInstance = is_string($this->action) ? $this->getActionClassInstance($this->action) : null;
        $docBlock = $actionInstance ? ($actionInstance->getDocComment() ?: "") : "";
        $classAnnotations = new Annotations($this->getActionClassInstance($this->action));

        if (Str::contains($docBlock, '@Resource')) {
            $resourceClass = new ReflectionClass($classAnnotations['Resource']);
            $docResponses = $resourceClass->getMethod('docResponses')->invoke(null);
        }
        $tags = collect(explode(',', $classAnnotations['tags'] ?? 'default'));
        $scopes = collect(explode(',', $classAnnotations['scopes'] ?? 'default'))->push('default');

        $tags = $tags->map(function ($tag) {
            return trim($tag);
        });

        $scopes = $scopes->map(function ($scope) {
            return trim($scope);
        });

        $this->tags = $this->tags->merge($tags)->unique();
        $this->scopes = $this->scopes->merge($scopes)->unique();

        list($isDeprecated, $summary, $description) = $this->parseActionDocBlock($docBlock);
        $this->docs['paths'][$this->uri][$this->method] = [
            'summary' => $summary,
            'description' => $description,
            'deprecated' => $isDeprecated,
            'responses' => $docResponses,
            'tags' => collect($tags),
            'scopes' => collect($scopes),
        ];

        $this->addActionParameters();
    }

    protected function addActionParameters()
    {
        $rules = $this->getFormRules() ?: [];

        $parameters = (new Parameters\PathParameterGenerator($this->originalUri))->getParameters();

        if (!empty($rules)) {
            $parameterGenerator = $this->getParameterGenerator($rules);

            $parameters = array_merge($parameters, $parameterGenerator->getParameters());
        }

        if (!empty($parameters)) {
            $this->docs['paths'][$this->uri][$this->method]['parameters'] = $parameters;
        }
    }

    protected function getFormRules()
    {
        if (!is_string($this->action)) {
            return false;
        }

        $parameters = $this->getActionClassInstance($this->action)->getParameters();
        foreach ($parameters as $parameter) {
            $class = $parameter->getClass();

            if (!$class) {
                continue;
            }

            $class_name = $class->getName();

            if (is_subclass_of($class_name, FormRequest::class)) {
                return (new $class_name)->rules();
            }
        }
    }

    protected function getParameterGenerator($rules)
    {
        switch ($this->method) {
            case 'post':
            case 'put':
            case 'patch':
                return new Parameters\BodyParameterGenerator($rules);
            default:
                return new Parameters\QueryParameterGenerator($rules);
        }
    }

    private function getActionClassInstance(string $action)
    {
        list($class, $method) = Str::parseCallback($action);

        return new ReflectionMethod($class, $method);
    }

    private function parseActionDocBlock(string $docBlock)
    {
        if (empty($docBlock) || !$this->config['parseDocBlock']) {
            return [false, "", ""];
        }

        try {
            $parsedComment = $this->docParser->create($docBlock);

            $isDeprecated = $parsedComment->hasTag('deprecated');

            $summary = $parsedComment->getSummary();
            $description = (string)$parsedComment->getDescription();

            return [$isDeprecated, $summary, $description];
        } catch (Exception $e) {
            return [false, "", ""];
        }
    }
}

<?php

namespace blockpit\LaravelSwagger\Parameters\Concerns;
use Illuminate\Support\Str;
use function PHPSTORM_META\type;

trait GeneratesFromRules
{
    protected function splitRules($rules)
    {
        if (is_string($rules)) {
            return explode('|', $rules);
        } else {
            return $rules;
        }
    }

    protected function getParamType(array $paramRules)
    {
        if (in_array('integer', $paramRules)) {
            return 'integer';
        } else if (in_array('numeric', $paramRules)) {
            return 'number';
        } else if (in_array('boolean', $paramRules)) {
            return 'boolean';
        } else if (in_array('array', $paramRules)) {
            return 'array';
        } else {
            //date, ip, email, etc..
            return 'string';
        }
    }

    protected function isParamRequired(array $paramRules)
    {
        return in_array('required', $paramRules);
    }

    protected function isArrayParameter($param)
    {
        return Str::contains($param, '*');
    }

    protected function getArrayKey($param)
    {
        return current(explode('.', $param));
    }

    protected function getEnumValues(array $paramRules)
    {
        $in = $this->getInParameter($paramRules);

        if (!$in) {
            return [];
        }

        list($param, $vals) = explode(':', $in);

        return explode(',', $vals);
    }

    private function getInParameter(array $paramRules)
    {
        foreach ($paramRules as $rule) {

            if (gettype($rule) == 'string' && Str::startsWith($rule, 'in:')) {
                return $rule;
            }
        }

        return false;
    }
}
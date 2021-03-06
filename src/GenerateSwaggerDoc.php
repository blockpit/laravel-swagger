<?php

namespace blockpit\LaravelSwagger;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateSwaggerDoc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'laravel-swagger:generate
                            {--format=json : The format of the output, current options are json and yaml}
                            {--filter= : Filter to a specific route prefix, such as /api or /v2/api}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically generates a swagger documentation file for this application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $config = config('laravel-swagger');

        $generatorOutput = (new Generator($config, $this->option('filter') ?: null))->generate();
        $fullDocs = $generatorOutput['docs'];
        $allPaths = collect($fullDocs['paths']);

        $tagPaths = [];
        $formattedDocsByScope = [];
        foreach ($generatorOutput['scopes'] as $scopes) {
            $tagsPaths[$scopes] = $allPaths->filter(function ($methods) use ($scopes) {
                $tags = collect($methods)->pluck('scopes')->toArray();
                if (sizeof($tags) == 0) {
                    return false;
                }
                $tags = collect(call_user_func_array('array_merge', $tags))->unique();
                return $tags->contains($scopes);
            });
            $tagDocs = $fullDocs;
            $tagDocs['paths'] = $tagsPaths[$scopes];
            $formattedDocsByScope[$scopes] = (new FormatterManager($tagDocs))->setFormat($this->option('format'))->format();
        }

        foreach ($formattedDocsByScope as $key => $docs) {
            $filename = $key . '.json';
            printf('writing to %s %s', $filename, PHP_EOL);
            Storage::disk('public')->put($filename, $docs);
        }

    }
}

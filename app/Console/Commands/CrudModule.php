<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud {name} {--namespace=: The namespace for the CRUD structure}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a complete  CRUD structure for a module';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $namespace = $this->option('namespace') ?? $name;
        $this->createController($name, $namespace);
        if($name !== 'User' && $name != 'user'){
            $this->createModel($name);
        }
        $this->createRequest($name, $namespace);
        $this->createRepository($name, $namespace);
        $this->createServiceInterface($name, $namespace);
        $this->createService($name, $namespace);
        // $this->createResource($name, $namespace);

    }

    private function createController($name, $namespace){
        $folder = app_path("Http/Controllers/Web/{$namespace}");
        File::ensureDirectoryExists($folder);
        $stub = $this->getStub('controller');
        $content = str_replace(
            [
                '{{namespace}}',
                '{{name}}',
                '{{snakeDot}}',
                '{{snakeName}}'
            ],
            [
                $namespace,
                $name,
                str_replace('_','.',Str::snake($name)),
                Str::snake($name)
            ],
            $stub
        );
        File::put("{$folder}/{$name}Controller.php", $content);
        
    }

    private function createModel($name){
        $stub = $this->getStub('model');
        $content = str_replace('{{name}}', $name, $stub);
        File::put(app_path("Models/{$name}.php"), $content);
    }

    private function createRequest($name, $namespace){
        $folder = app_path("Http/Requests/{$namespace}/{$name}");
        File::ensureDirectoryExists($folder);

        $requests = [
            'StoreRequest' => 'store-request',
            'UpdateRequest' => 'update-request',
        ];

        foreach($requests as $key => $stubName){
            $stub = $this->getStub($stubName);
            $content = str_replace(
                [
                    '{{namespace}}',
                    '{{name}}',
                    '{{snakeCase}}'
                ],
                [
                    $namespace,
                    $name,
                    Str::snake($name)
                ],
                $stub
            );
            File::put("{$folder}/{$key}.php", $content);
        }

    }

    private function createRepository($name, $namespace){
        $folder = app_path("Repositories/{$namespace}");
        File::ensureDirectoryExists($folder);
        $stub = $this->getStub('repository');
        $content = str_replace(
            [
                '{{namespace}}',
                '{{name}}',
            ],
            [
                $namespace,
                $name
            ],
            $stub
        );

        File::put("{$folder}/{$name}Repository.php", $content);
    }

    private function createServiceInterface($name, $namespace){
        $folder = app_path("Services/Interfaces/{$namespace}");
        File::ensureDirectoryExists($folder);
        $stub = $this->getStub('service-interface');
        $content = str_replace(
            [
                '{{namespace}}',
                '{{name}}',
            ],
            [
                $namespace,
                $name
            ],
            $stub
        );
        File::put("{$folder}/{$name}ServiceInterface.php", $content);
    }

    private function createService($name, $namespace){
        $folder = app_path("Services/Impl/{$namespace}");
        File::ensureDirectoryExists($folder);
        $stub = $this->getStub('service');
        $content = str_replace(
            [
                '{{namespace}}',
                '{{name}}',
                '{{snakeCase}}'
            ],
            [
                $namespace,
                $name,
                Str::snake($name)
            ],
            $stub
        );
        File::put("{$folder}/{$name}Service.php", $content);
    }

    // private function createResource($name, $namespace){
    //     $folder = app_path("Http/Resources/{$namespace}");
    //     File::ensureDirectoryExists($folder);
    //     $stub = $this->getStub('resource');
    //     $content = str_replace(
    //         [
    //             '{{namespace}}',
    //             '{{name}}',
    //         ],
    //         [
    //             $namespace,
    //             $name,
    //         ],
    //         $stub
    //     );
    //     File::put("{$folder}/{$name}Resource.php", $content);
    // }


    private function getStub($type){
        return File::get(resource_path("stubs/{$type}.stub"));
    }
}

<?php

namespace Etlok\Crux\Console;

use Illuminate\Console\GeneratorCommand;

class BuildWebSocketController extends GeneratorCommand
{
    protected $name = 'build:websocket:controller';

    protected $description = 'Create a new crux controller for websockets';

    protected $type = 'CruxWebSocketController';

    protected function getStub()
    {
        $stub = '/stubs/crux_websockets/controller.php.stub';
        return $this->resolveStubPath($stub);
    }

    /**
     * Resolve the fully-qualified path to the stub.
     *
     * @param  string  $stub
     * @return string
     */
    protected function resolveStubPath($stub)
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\WebSockets\Controllers';
    }

    public function handle()
    {
        parent::handle();
    }

    /**
     * Generate the form requests for the given model and classes.
     *
     * @param  string  $modelName
     * @param  string  $storeRequestClass
     * @param  string  $updateRequestClass
     * @return array
     */
    protected function generateFormRequests($modelClass, $storeRequestClass, $updateRequestClass)
    {
        $storeRequestClass = 'Store'.class_basename($modelClass).'Request';

        $this->call('build:request', [
            'name' => $storeRequestClass,
        ]);

        $updateRequestClass = 'Update'.class_basename($modelClass).'Request';

        $this->call('build:request', [
            'name' => $updateRequestClass,
        ]);

        return [$storeRequestClass, $updateRequestClass];
    }
}
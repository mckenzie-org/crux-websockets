<?php

namespace Etlok\Crux\WebSockets\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCruxWebSockets extends Command
{
    protected $signature = 'crux:websockets:install';

    protected $description = 'Install the Crux Web Sockets package';

    public function handle()
    {
        $this->info('Installing Crux WebSockets...');

        $this->info('Publishing configuration...');

        if (! $this->configExists('crux_websockets.php')) {
            $this->publishConfiguration();
            $this->info('Published Configurations');
        } else {
            if ($this->shouldOverwriteConfig()) {
                $this->info('Overwriting Configuration file...');
                $this->publishConfiguration($force = true);
            } else {
                $this->info('Existing Configuration was not overwritten');
            }
        }

        if (! $this->routeExists()) {
            $this->publishRoutes();
            $this->info('Published Routes');
        } else {
            if ($this->shouldOverwriteRoute()) {
                $this->info('Overwriting Route file...');
                $this->publishRoutes($force = true);
            } else {
                $this->info('Existing Routes were not overwritten');
            }
        }

        $this->info('Crux Installation Complete!');
    }

    private function configExists($fileName)
    {
        return File::exists(config_path($fileName));
    }

    private function routeExists()
    {
        return File::exists(base_path("routes/websockets/client.php"));
    }

    private function shouldOverwriteConfig()
    {
        return $this->confirm(
            'Config file already exists. Do you want to overwrite it?',
            false
        );
    }

    private function shouldOverwriteRoute()
    {
        return $this->confirm(
            'Route file already exists. Do you want to overwrite it?',
            false
        );
    }

    private function publishConfiguration($forcePublish = false)
    {
        $params = [
            '--provider' => "Etlok\Crux\WebSockets\CruxWebSocketsServiceProvider",
            '--tag' => "config"
        ];

        if ($forcePublish === true) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }

    private function publishRoutes($forcePublish = false)
    {
        $params = [
            '--provider' => "Etlok\Crux\WebSockets\CruxWebSocketsServiceProvider",
            '--tag' => "routes"
        ];

        if ($forcePublish === true) {
            $params['--force'] = true;
        }

        $this->call('vendor:publish', $params);
    }
}
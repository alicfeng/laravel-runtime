<?php
/**
 * Created by PhpStorm   User: AlicFeng   DateTime: 19-3-20 上午11:35
 */

namespace AlicFeng\Runtime\ServiceProvider;


use AlicFeng\Runtime\Command\RuntimeAnalysisCommand;
use Illuminate\Support\ServiceProvider;

class RuntimeServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/runtime.php', 'runtime');
    }

    public function boot()
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'runtime');
        $this->publishConfig();
        $this->registerCommand();
        $this->registerRoute();
    }

    public function registerCommand()
    {
        if ($this->app->runningInConsole()) {
            $commands = [
                RuntimeAnalysisCommand::class,
            ];
            $this->commands($commands);
        }
    }

    public function registerRoute()
    {
        $routeConfig = [
            'namespace' => 'AlicFeng\Runtime\Http\Controllers',
            'prefix'    => 'runtime',
        ];
        $this->getRouter()->group($routeConfig, function ($router) {
            // html page get
            $router->get('analysis', [
                'uses' => 'AnalysisController@analysis',
                'as'   => 'analysis',
            ]);

            // date list | post
            $router->post('list', [
                'uses' => 'AnalysisController@list',
                'as'   => 'list',
            ]);

            // date list | get
            $router->get('clear', [
                'uses' => 'AnalysisController@clear',
                'as'   => 'clear',
            ]);

            // date list | get
            $router->get('reload', [
                'uses' => 'AnalysisController@reload',
                'as'   => 'reload',
            ]);
        });
    }

    public function publishConfig()
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/runtime.php' => config_path('runtime.php'),
                __DIR__ . '/../../resources/views'    => resource_path('views/runtime'),
            ],
            'runtime'
        );
    }

    /**
     * Get the active router.
     *
     * @return Router
     */
    protected function getRouter()
    {
        return $this->app['router'];
    }
}
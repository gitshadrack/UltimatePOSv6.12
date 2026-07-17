<?php

namespace Modules\PageSpeed\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\DB;
use App\Utils\ModuleUtil;

class PageSpeedServiceProvider extends ServiceProvider
{
    /**
     * The middleware to register
     *
     * @var array
     */
    protected $middleware = [
        'PageSpeed' => [
            'PageSpeedOptimization' => 'PageSpeedOptimization',
        ],
    ];

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->registerMiddleware($this->app['router']);
        $this->registerPageSpeedOptimization();
    }

    /**
     * Register the middleware.
     *
     * @param  Router $router
     * @return void
     */
    public function registerMiddleware(Router $router)
    {
        foreach ($this->middleware as $module => $middlewares) {
            foreach ($middlewares as $name => $middleware) {
                $class = "Modules\\{$module}\\Http\\Middleware\\{$middleware}";
                $router->aliasMiddleware($name, $class);
            }
        }
    }

    /**
     * Register PageSpeed optimization - 100% Independent Module
     * NO core file modification needed!
     *
     * @return void
     */
    protected function registerPageSpeedOptimization()
    {
        try {
            $module_util = new ModuleUtil();
            $is_installed = $module_util->isModuleInstalled('PageSpeed');

            if (!$is_installed) {
                return;
            }

            // Approach: Use pushMiddleware to inject globally
            // This runs in the middleware stack BEFORE response is sent
            $kernel = $this->app[\Illuminate\Contracts\Http\Kernel::class];
            $kernel->pushMiddleware(\Modules\PageSpeed\Http\Middleware\PageSpeedOptimization::class);

        } catch (\Exception $e) {
            // Silently fail if not installed yet
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);

        // Register PageSpeed Service
        $this->app->singleton('pagespeed', function ($app) {
            return new \Modules\PageSpeed\Services\PageSpeedService();
        });
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__ . '/../Config/config.php' => config_path('pagespeed.php'),
        ], 'config');

        $this->mergeConfigFrom(
            __DIR__ . '/../Config/config.php',
            'pagespeed'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/pagespeed');
        $sourcePath = __DIR__ . '/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath,
        ], 'views');

        $overridePaths = array_filter(array_map(function ($path) {
            return $path . '/modules/pagespeed';
        }, config('view.paths')), 'is_dir');

        $this->loadViewsFrom(array_merge($overridePaths, [$sourcePath]), 'pagespeed');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/pagespeed');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'pagespeed');
        } else {
            $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'pagespeed');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['pagespeed'];
    }
}

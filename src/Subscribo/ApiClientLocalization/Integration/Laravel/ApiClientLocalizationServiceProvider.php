<?php namespace Subscribo\ApiClientLocalization\Integration\Laravel;

use Illuminate\Routing\Router;
use Subscribo\Support\ServiceProvider;
use Subscribo\ApiClientLocalization\LocalePossibilities;
use Subscribo\Localization\Integration\Laravel\LocalizationServiceProvider;

/**
 * Class ApiClientLocalizationServiceProvider
 *
 * @package Subscribo\ApiClient
 */
class ApiClientLocalizationServiceProvider extends ServiceProvider {

    protected $defer = false;

    /** @var bool  */
    protected $routesRegistered = false;

    public function register()
    {
        /** @var LocalizationServiceProvider $localizationServiceProvider */
        $localizationServiceProvider = $this->app->register('\\Subscribo\\Localization\\Integration\\Laravel\\LocalizationServiceProvider');
        if (true === $localizationServiceProvider) {
            $localizationServiceProvider = $this->app->getProvider('\\Subscribo\\Localization\\Integration\\Laravel\\LocalizationServiceProvider');
        }
        $localizationServiceProvider->registerLocaleSettingsManager(
            function ($app) {
                $configManager = $app->make('subscribo.config');
                $configManager->loadFileForPackage('apiclientlocalization', 'default', true, true, true);
                $data = $configManager->getForPackage('apiclientlocalization', 'default.localeSettings', array());
                return $data;
            },
            null,
            '\\Subscribo\\ApiClientLocalization\\LocalePossibilities',
            null
        );


    }

    public function boot()
    {
        $packageDir = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        $this->loadViewsFrom($packageDir.'/resources/views', 'subscribo');

        $this->app->make('view')->composer('subscribo::apiclientlocalization/localeselector', 'Subscribo\\ApiClientLocalization\\ViewComposers\\LocaleSelectorComposer');
        $this->publishes([$packageDir.'/install/laravel/subscribo/config/packages/apiclientlocalization/default.yml' => base_path('subscribo/config/packages/apiclientlocalization/default.yml')], 'config');
        $this->publishes([$packageDir.'/resources/views/apiclientlocalization/localeselector.blade.php' => base_path('resources/views/vendor/subscribo/apiclientlocalization/localeselector.blade.php')], 'view');
        $this->publishes([$packageDir.'/resources/views/app/' => base_path('resources/views/')], 'translated_views');

        $this->registerTranslationResources(['main', 'auth'], 'app');
    }

    public function registerRoutes(Router $router, array $middleware, array $paths = array())
    {
        if ($this->routesRegistered) {
            return;
        }
        $defaultPaths = [
            'subscribo.localization.setting.ajax' => '/locale',
            'subscribo.localization.setting.redirect' => '/locale/{locale}',
        ];
        $paths = array_replace($defaultPaths, $paths);

        $router->post($paths['subscribo.localization.setting.ajax'], ['as' => 'subscribo.localization.setting.ajax', 'middleware' => $middleware, 'uses' => '\\Subscribo\\ApiClientLocalization\\Controllers\\LocaleController@postLocaleAjax']);
        $router->get($paths['subscribo.localization.setting.redirect'], ['as' => 'subscribo.localization.setting.redirect', 'middleware' => $middleware, 'uses' => '\\Subscribo\\ApiClientLocalization\\Controllers\\LocaleController@getLocaleRedirectBack'])->where(['locale' => '[A-Za-z0-9_-]+']);

        $this->routesRegistered = true;
    }

}

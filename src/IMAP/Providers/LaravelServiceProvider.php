<?php
/*
* File:     LaravelServiceProvider.php
* Category: Provider
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Webklex\IMAP\Providers;

use Illuminate\Support\ServiceProvider;
use Webklex\IMAP\Client;
use Webklex\IMAP\ClientManager;

/**
 * Class LaravelServiceProvider
 *
 * @package Webklex\IMAP\Providers
 */
class LaravelServiceProvider extends ServiceProvider {

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot() {
        $this->publishes([
            __DIR__.'/../../config/imap.php' => config_path('imap.php'),
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(ClientManager::class, function($app) {
            return new ClientManager($app);
        });

        $this->app->singleton(Client::class, function($app) {
            return $app[ClientManager::class]->account();
        });

        $this->setVendorConfig();
    }

    /**
     * Merge the vendor settings with the local config
     *
     * The default account identifier will be used as default for any missing account parameters.
     * If however the default account is missing a parameter the package default account parameter will be used.
     * This can be disabled by setting imap.default in your config file to 'false'
     */
    private function setVendorConfig(){

        $config_key = 'imap';
        $path = __DIR__.'/../../config/'.$config_key.'.php';

        $vendor_config = require $path;
        $config = $this->app['config']->get($config_key, []);

        $this->app['config']->set($config_key, array_merge($vendor_config, $config));

        $config = $this->app['config']->get($config_key);

        if(is_array($config)){
            if(isset($config['default'])){
                if(isset($config['accounts']) && $config['default'] != false){

                    $default_config = $vendor_config['accounts']['default'];
                    if(isset($config['accounts'][$config['default']])){
                        $default_config = array_merge($default_config, $config['accounts'][$config['default']]);
                    }

                    if(is_array($config['accounts'])){
                        foreach($config['accounts'] as $account_key => $account){
                            $config['accounts'][$account_key] = array_merge($default_config, $account);
                        }
                    }
                }
            }
        }

        $this->app['config']->set($config_key, $config);
    }
}
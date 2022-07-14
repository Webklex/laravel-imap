<?php
/*
* File:     Client.php
* Category: Facade
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Grkztd\IMAP\Facades;

use Illuminate\Support\Facades\Facade;
use Grkztd\PHPIMAP\ClientManager;

/**
 * Class Client
 *
 * @package Grkztd\IMAP\Facades
 *
 * @method \Grkztd\PHPIMAP\Client account($name = null)
 * @method \Grkztd\PHPIMAP\Client make($options = [])
 */
class Client extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() {
        return ClientManager::class;
    }
}
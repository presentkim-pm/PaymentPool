<?php

/*
 *
 *  ____  _             _         _____
 * | __ )| |_   _  __ _(_)_ __   |_   _|__  __ _ _ __ ___
 * |  _ \| | | | |/ _` | | '_ \    | |/ _ \/ _` | '_ ` _ \
 * | |_) | | |_| | (_| | | | | |   | |  __/ (_| | | | | | |
 * |____/|_|\__,_|\__, |_|_| |_|   |_|\___|\__,_|_| |_| |_|
 *                |___/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\api\paymentpool;

use pocketmine\plugin\PluginBase;

class PaymentPool extends PluginBase{
    /** @var PaymentPool|null */
    private static $instance = null;

    /** @return PaymentPool|null */
    public static function getInstance() : ?PaymentPool{
        return self::$instance;
    }

    /** @var IPaymentProvider[] name => economy provider */
    private $providers = [];
    private $default = null;

    public function onLoad(){
        self::$instance = $this;
    }

    /** @return IPaymentProvider[] */
    public function getProviders() : array{
        return $this->providers;
    }

    /**
     * @param string|null $uniqueName If it was null, return default provider
     *
     * @return IPaymentProvider|null
     */
    public function getProvider(?string $uniqueName = null) : ?IPaymentProvider{
        if($uniqueName === null){
            $uniqueName = $this->default === null ? array_key_first($this->providers) : $this->default;
        }

        return $this->providers[strtolower($uniqueName)] ?? null;
    }

    /**
     * @param IPaymentProvider $provider
     * @param string[]         $saveNames
     */
    public function registerProvider(IPaymentProvider $provider, array $saveNames = []) : void{
        if($this->default === null){
            $this->default = $provider->getName();
        }

        $saveNames[] = $provider->getName();
        foreach($saveNames as $name){
            $this->providers[strtolower($name)] = $provider;
        }
    }
}

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
    /** @var IPaymentProvider[] name => economy provider */
    private static $providers = [];

    /** @var string|null */
    private static $default = null;

    /** @return IPaymentProvider[] */
    public static function getProviders() : array{
        return self::$providers;
    }

    /**
     * @param string|null $uniqueName If it was null, return default provider
     *
     * @return IPaymentProvider|null
     */
    public static function getProvider(?string $uniqueName = null) : ?IPaymentProvider{
        if($uniqueName === null){
            $uniqueName = self::$default === null ? array_key_first(self::$providers) : self::$default;
        }

        return self::$providers[strtolower($uniqueName)] ?? null;
    }

    /**
     * @param IPaymentProvider $provider
     * @param string[]         $saveNames
     */
    public static function registerProvider(IPaymentProvider $provider, array $saveNames = []) : void{
        if(self::$default === null){
            self::$default = $provider->getName();
        }

        $saveNames[] = $provider->getName();
        foreach($saveNames as $name){
            self::$providers[strtolower($name)] = $provider;
        }
    }

    /** @return string|null */
    public static function getDefault() : ?string{
        return self::$default ?? (empty(self::$providers) ? null : array_key_first(self::$providers));
    }

    /** @param string|null $default */
    public static function setDefault(?string $default) : void{
        self::$default = $default;
    }
}

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

use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;

class PaymentPool extends PluginBase{
    /** @var IPaymentProvider[] name => economy provider */
    private static $providers = [];
    /** @var IPaymentProvider[] save name => economy provider */
    private static $providerSaveNames = [];

    /** @var string|null */
    private static $default = null;

    /** @var PluginInfo[] plugin name => plugin info */
    private static $infos = [];

    /** @return IPaymentProvider[] */
    public static function getProviders() : array{
        return self::$providers;
    }

    public function onEnable() : void{
        //Load plugin info data
        $filePath = "{$this->getDataFolder()}infos.json";
        if(!file_exists($filePath))
            return;

        $content = file_get_contents($filePath);
        if($content === false)
            throw new \RuntimeException("Unable to find infos.json file");

        $infoData = json_decode($content, true);
        if(!is_array($infoData))
            throw new \RuntimeException("Unable to decode infos.json file");

        $infos = [];
        foreach(json_decode($content, true) as $value){
            $info = PluginInfo::jsonDeserialize($value);
            if($info === null)
                throw new \RuntimeException("Unable to load infos.json file");

            $infos[$info->getName()] = $info;
        }
        self::$infos = $infos;
    }

    public function onDisable() : void{
        //Save plugin info data
        if(!empty(self::$infos)){
            $filePath = "{$this->getDataFolder()}infos.json";
            file_put_contents($filePath, json_encode(self::$infos, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
            self::$infos = [];
        }
    }

    /**
     * @param Plugin|string|null $option If it was null, return default provider
     *
     * @return IPaymentProvider|null
     */
    public static function getProvider($option = null) : ?IPaymentProvider{
        $providerName = null;
        if($option instanceof Plugin && isset(self::$infos[$option->getName()])){
            $providerName = self::$infos[$option->getName()]->getDefault();
        }

        $providerName = strtolower($providerName ?? "");
        return self::$providers[$providerName] ?? self::$providerSaveNames[$providerName] ?? self::$providers[strtolower(self::getDefault())] ?? null;
    }

    /**
     * @param IPaymentProvider $provider
     * @param string[]         $saveNames
     */
    public static function registerProvider(IPaymentProvider $provider, array $saveNames = []) : void{
        if(self::$default === null){
            self::$default = $provider->getName();
        }

        self::$providers[strtolower($provider->getName())] = $provider;
        foreach($saveNames as $name){
            self::$providerSaveNames[strtolower($name)] = $provider;
        }
    }

    /** @return string[] */
    public static function getInfos() : array{
        return self::$infos;
    }

    /** @param Plugin $plugin */
    public static function registerPlugin(Plugin $plugin) : void{
        $id = spl_object_id($plugin);
        if(!isset(self::$infos[$id])){
            self::$infos[$plugin->getName()] = new PluginInfo($plugin->getName(), self::getDefault());
        }
    }

    /**
     * @param $plugin
     *
     * @return PluginInfo|null
     */
    public static function getPluginInfo($plugin) : ?PluginInfo{
        if($plugin instanceof Plugin){
            $plugin = $plugin->getName();
        }

        return self::$infos[$plugin] ?? null;
    }

    /** @return PluginInfo[] */
    public static function getPluginInfos() : array{
        return self::$infos;
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

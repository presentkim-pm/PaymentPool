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

use blugin\api\paymentpool\command\DefaultSubcommand;
use blugin\api\paymentpool\command\ListSubcommand;
use blugin\api\paymentpool\command\PluginsSubcommand;
use blugin\api\paymentpool\command\SetSubcommand;
use blugin\lib\command\SubcommandTrait;
use blugin\lib\translator\MultilingualConfigTrait;
use blugin\lib\translator\TranslatorHolder;
use blugin\lib\translator\TranslatorHolderTrait;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;

class PaymentPool extends PluginBase implements TranslatorHolder{
    use TranslatorHolderTrait, MultilingualConfigTrait, SubcommandTrait;

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

    public function onLoad(){
        $this->loadLanguage($this->getConfig()->getNested("settings.language"));
        $this->getMainCommand("payment");
    }

    public function onEnable() : void{
        //Register main command with subcommands
        $command = $this->getMainCommand("payment");
        $command->registerSubcommand(new DefaultSubcommand($command));
        $command->registerSubcommand(new SetSubcommand($command));
        $command->registerSubcommand(new ListSubcommand($command));
        $command->registerSubcommand(new PluginsSubcommand($command));
        $this->recalculatePermissions();
        $this->getServer()->getCommandMap()->register($this->getName(), $command);

        //Load plugin info data
        $filePath = "{$this->getDataFolder()}data.json";
        if(!file_exists($filePath))
            return;

        $content = file_get_contents($filePath);
        if($content === false)
            throw new \RuntimeException("Unable to find data.json file");

        $data = json_decode($content, true);
        if(!is_array($data) || !is_string($data["default"] ?? null) || !is_array($data["infos"] ?? null))
            throw new \RuntimeException("Unable to decode data.json file");

        $default = self::get($data["default"]);
        if($default !== null){
            self::setDefault($default->getName());
        }

        $infos = [];
        foreach($data["infos"] as $infoData){
            try{
                $info = PluginInfo::jsonDeserialize($infoData);
                $infos[$info->getName()] = $info;
            }catch(\Exception $e){
                throw new \RuntimeException("[data.json] Unable to parse info data");
            }
        }
        self::$infos = $infos;
    }

    public function onDisable() : void{
        //Unregister main command with subcommands
        $this->getServer()->getCommandMap()->unregister($this->getMainCommand("payment"));

        //Save plugin info data
        $filePath = "{$this->getDataFolder()}data.json";
        file_put_contents($filePath, json_encode([
            "default" => self::getDefault(),
            "infos" => self::$infos
        ], JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
        self::$infos = [];
    }

    /**
     * @param Plugin|string|null $option If it was null, return default provider
     * @param bool               $default = true
     *
     * @return IPaymentProvider|null
     */
    public static function get($option = null, bool $default = true) : ?IPaymentProvider{
        $providerName = null;
        if($option instanceof Plugin && isset(self::$infos[$option->getName()])){
            $providerName = self::$infos[$option->getName()]->getDefault();
        }elseif(is_string($option)){
            if(isset(self::$infos[$option])){
                $providerName = self::$infos[$option]->getDefault();
            }else{
                $providerName = $option;
            }
        }

        $providerName = strtolower($providerName ?? "");
        $provider = self::$providers[$providerName] ?? self::$providerSaveNames[$providerName] ?? null;
        if($provider !== null && $default){
            $provider = self::$providers[strtolower(self::getDefault())] ?? null;
        }

        return $provider;
    }

    /**
     * @param IPaymentProvider $provider
     * @param string[]         $saveNames
     */
    public static function register(IPaymentProvider $provider, array $saveNames = []) : void{
        if(self::$default === null){
            self::$default = $provider->getName();
        }

        self::$providers[strtolower($provider->getName())] = $provider;
        foreach($saveNames as $name){
            self::$providerSaveNames[strtolower($name)] = $provider;
        }
    }

    /**
     * @param Plugin|string $plugin
     *
     * @return PluginInfo|null
     */
    public static function getPluginInfo($plugin) : ?PluginInfo{
        if($plugin instanceof Plugin){
            $plugin = $plugin->getName();
        }

        return self::$infos[$plugin] ?? null;
    }

    /** @param Plugin|string $plugin */
    public static function createPluginInfo($plugin) : void{
        if($plugin instanceof Plugin){
            $plugin = $plugin->getName();
        }

        if(!isset(self::$infos[$plugin])){
            self::$infos[$plugin] = new PluginInfo($plugin, self::getDefault());
        }
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

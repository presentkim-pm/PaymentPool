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

use blugin\api\paymentpool\command\overload\ListOverload;
use blugin\api\paymentpool\command\overload\PluginsOverload;
use blugin\api\paymentpool\command\overload\SetOverload;
use blugin\lib\command\BaseCommandTrait;
use blugin\lib\command\enum\Enum;
use blugin\lib\command\enum\EnumFactory;
use blugin\lib\command\translator\traits\TranslatorHolderTrait;
use blugin\lib\command\translator\TranslatorHolder;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;

class PaymentPool extends PluginBase implements TranslatorHolder{
    use TranslatorHolderTrait, BaseCommandTrait;

    public const DEFAULT_NAME = "@";
    public const ENUM_PROVIDERS = "Payment";
    public const ENUM_PLUGININFOS = "PaymentPlugin";

    /** @var Enum name => IPaymentProvider */
    private static $providerEnum;
    /** @var IPaymentProvider[] save name => economy provider */
    private static $providerSaveNames = [];

    /** @var Enum name => PluginInfo */
    private static $pluginInfoEnum;

    /** @return IPaymentProvider[] name => provider */
    public static function getProviders() : array{
        return self::$providerEnum->getAll();
    }

    public static function getProviderEnum() : Enum{
        return self::$providerEnum;
    }

    public static function getPluginInfoEnum() : Enum{
        return self::$pluginInfoEnum;
    }

    public function onLoad(){
        self::$providerEnum = EnumFactory::getInstance()->set(self::ENUM_PROVIDERS);
        self::$pluginInfoEnum = EnumFactory::getInstance()->set(self::ENUM_PLUGININFOS);
        self::createPluginInfo(self::DEFAULT_NAME);

        $this->loadLanguage();
        $this->getBaseCommand("payment");
    }

    public function onEnable() : void{
        //Register main command with subcommands
        $command = $this->getBaseCommand("payment");
        $command->addOverload(new SetOverload($command));
        $command->addOverload(new ListOverload($command));
        $command->addOverload(new PluginsOverload($command));
        $this->getServer()->getCommandMap()->register($this->getName(), $command);

        //Load plugin info data
        $filePath = "{$this->getDataFolder()}data.json";
        if(!file_exists($filePath))
            return;

        $content = file_get_contents($filePath);
        if($content === false)
            throw new \RuntimeException("Unable to find data.json file");

        $data = json_decode($content, true);
        if(!is_array($data))
            throw new \RuntimeException("Unable to decode data.json file");

        foreach($data as $infoData){
            try{
                $info = PluginInfo::jsonDeserialize($infoData);
                self::$pluginInfoEnum->set($info->getName(), $info);
            }catch(\Exception $e){
                self::$pluginInfoEnum->setAll([]);
                throw new \RuntimeException("[data.json] Unable to parse info data");
            }
        }
    }

    public function onDisable() : void{
        //Unregister main command with subcommands
        $this->getServer()->getCommandMap()->unregister($this->getBaseCommand("payment"));

        //Save plugin info data
        $filePath = "{$this->getDataFolder()}data.json";
        file_put_contents($filePath, json_encode(self::$pluginInfoEnum, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
        self::$pluginInfoEnum->setAll([]);
    }

    /**
     * @param Plugin|string|null $option If it was null, return default provider
     * @param bool               $default = true
     *
     * @return IPaymentProvider|null
     */
    public static function get($option = null, bool $default = true) : ?IPaymentProvider{
        $providerName = null;
        if($option instanceof Plugin && isset(self::$pluginInfoEnum[$option->getName()])){
            $providerName = self::$pluginInfoEnum[$option->getName()]->getDefault();
        }elseif(is_string($option)){
            if(self::$pluginInfoEnum->has($option)){
                $providerName = self::$pluginInfoEnum->get($option)->getDefault();
            }else{
                $providerName = $option;
            }
        }

        $providerName = strtolower($providerName ?? "");
        $provider = self::$providerEnum->get($providerName) ?? self::$providerSaveNames[$providerName] ?? null;
        if($provider !== null && $default){
            $provider = self::$providerEnum->get(strtolower(self::getDefault())) ?? null;
        }

        return $provider;
    }

    /**
     * @param IPaymentProvider $provider
     * @param string[]         $saveNames
     */
    public static function register(IPaymentProvider $provider, array $saveNames = []) : void{
        if(self::getDefault() === null){
            self::setDefault($provider->getName());
        }

        self::$providerEnum->set(strtolower($provider->getName()), $provider);
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

        return self::$pluginInfoEnum[$plugin] ?? null;
    }

    /** @param Plugin|string $plugin */
    public static function createPluginInfo($plugin) : void{
        if($plugin instanceof Plugin){
            $plugin = $plugin->getName();
        }

        if(self::$pluginInfoEnum->has($plugin)){
            self::$pluginInfoEnum->set($plugin, new PluginInfo($plugin, self::getDefault()));
        }
    }

    /** @return PluginInfo[] */
    public static function getPluginInfos() : array{
        return self::$pluginInfoEnum->getAll();
    }

    /** @return string|null */
    public static function getDefault() : ?string{
        $providers = self::getProviders();
        return self::getPluginInfo(self::DEFAULT_NAME)->getDefault() ?? (empty($providers) ? null : array_key_first($providers));
    }

    /** @param string|null $default */
    public static function setDefault(?string $default) : void{
        self::getPluginInfo(self::DEFAULT_NAME)->setDefault($default);
    }
}

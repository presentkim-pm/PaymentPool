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

    private static $instance;

    public static function getInstance() : PaymentPool{
        return self::$instance;
    }

    public const DEFAULT_NAME = "@";
    public const ENUM_PROVIDERS = "Payment";
    public const ENUM_PLUGININFOS = "PaymentPlugin";

    /** @var Enum name => IPaymentProvider */
    private $providerEnum;
    /** @var IPaymentProvider[] save name => economy provider */
    private $providerSaveNames = [];

    /** @var Enum name => PluginInfo */
    private $pluginInfoEnum;

    /** @return IPaymentProvider[] name => provider */
    public function getProviders() : array{
        return $this->providerEnum->getAll();
    }

    public function getProviderEnum() : Enum{
        return $this->providerEnum;
    }

    public function getPluginInfoEnum() : Enum{
        return $this->pluginInfoEnum;
    }

    public function onLoad(){
        self::$instance = $this;

        $this->providerEnum = EnumFactory::getInstance()->set(self::ENUM_PROVIDERS);
        $this->pluginInfoEnum = EnumFactory::getInstance()->set(self::ENUM_PLUGININFOS);
        $this->createPluginInfo(self::DEFAULT_NAME);

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
                $this->pluginInfoEnum->set($info->getName(), $info);
            }catch(\Exception $e){
                $this->pluginInfoEnum->setAll([]);
                throw new \RuntimeException("[data.json] Unable to parse info data");
            }
        }
    }

    public function onDisable() : void{
        //Unregister main command with subcommands
        $this->getServer()->getCommandMap()->unregister($this->getBaseCommand("payment"));

        //Save plugin info data
        $filePath = "{$this->getDataFolder()}data.json";
        file_put_contents($filePath, json_encode($this->pluginInfoEnum, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
        $this->pluginInfoEnum->setAll([]);
    }

    /**
     * @param Plugin|string|null $option If it was null, return default provider
     * @param bool               $default = true
     *
     * @return IPaymentProvider|null
     */
    public function get($option = null, bool $default = true) : ?IPaymentProvider{
        $providerName = null;
        if($option instanceof Plugin && isset($this->pluginInfoEnum[$option->getName()])){
            $providerName = $this->pluginInfoEnum[$option->getName()]->getDefault();
        }elseif(is_string($option)){
            if($this->pluginInfoEnum->has($option)){
                $providerName = $this->pluginInfoEnum->get($option)->getDefault();
            }else{
                $providerName = $option;
            }
        }

        $providerName = strtolower($providerName ?? "");
        $provider = $this->providerEnum->get($providerName) ?? $this->providerSaveNames[$providerName] ?? null;
        if($provider !== null && $default){
            $provider = $this->providerEnum->get(strtolower($this->getDefault())) ?? null;
        }

        return $provider;
    }

    /**
     * @param IPaymentProvider $provider
     * @param string[]         $saveNames
     */
    public function register(IPaymentProvider $provider, array $saveNames = []) : void{
        if($this->getDefault() === null){
            $this->setDefault($provider->getName());
        }

        $this->providerEnum->set(strtolower($provider->getName()), $provider);
        foreach($saveNames as $name){
            $this->providerSaveNames[strtolower($name)] = $provider;
        }
    }

    /**
     * @param Plugin|string $plugin
     *
     * @return PluginInfo|null
     */
    public function getPluginInfo($plugin) : ?PluginInfo{
        if($plugin instanceof Plugin){
            $plugin = $plugin->getName();
        }

        return $this->pluginInfoEnum[$plugin] ?? null;
    }

    /** @param Plugin|string $plugin */
    public function createPluginInfo($plugin) : void{
        if($plugin instanceof Plugin){
            $plugin = $plugin->getName();
        }

        $this->pluginInfoEnum->set($plugin, new PluginInfo($plugin, $this->getDefault()));
    }

    /** @return PluginInfo[] */
    public function getPluginInfos() : array{
        return $this->pluginInfoEnum->getAll();
    }

    /** @return string|null */
    public function getDefault() : ?string{
        $providers = $this->getProviders();
        return $this->getPluginInfo(self::DEFAULT_NAME)->getDefault() ?? (empty($providers) ? null : array_key_first($providers));
    }

    /** @param string|null $default */
    public function setDefault(?string $default) : void{
        $this->getPluginInfo(self::DEFAULT_NAME)->setDefault($default);
    }
}

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

use blugin\api\paymentpool\command\overload\LinksOverload;
use blugin\api\paymentpool\command\overload\PaymentsOverload;
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
    public const ENUM_PLUGININFOS = "PaymentLink";

    /** @var Enum name => IPaymentProvider */
    private $providerEnum;

    /** @var Enum name => PaymentLink */
    private $linkEnum;

    /** @var IPaymentProvider[] save name => economy provider */
    private $providerSaveNames = [];

    public function onLoad(){
        self::$instance = $this;

        $this->providerEnum = EnumFactory::getInstance()->set(self::ENUM_PROVIDERS);
        $this->linkEnum = EnumFactory::getInstance()->set(self::ENUM_PLUGININFOS);
        $this->registerLink(self::DEFAULT_NAME);
        var_dump($this->linkEnum);

        $this->loadLanguage();
        $this->getBaseCommand("payment");
    }

    public function onEnable() : void{
        //Register main command with subcommands
        $command = $this->getBaseCommand("payment");
        $command->addOverload(new SetOverload($command));
        $command->addOverload(new PaymentsOverload($command));
        $command->addOverload(new LinksOverload($command));
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
                $info = PaymentLink::jsonDeserialize($infoData);
                $this->linkEnum->set($info->getName(), $info);
            }catch(\Exception $e){
                $this->linkEnum->setAll([]);
                throw new \RuntimeException("[data.json] Unable to parse info data");
            }
        }
    }

    public function onDisable() : void{
        //Unregister main command with subcommands
        $this->getServer()->getCommandMap()->unregister($this->getBaseCommand("payment"));

        //Save plugin info data
        $filePath = "{$this->getDataFolder()}data.json";
        file_put_contents($filePath, json_encode($this->getLinks(), JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING));
        $this->linkEnum->setAll([]);
    }

    public function getProviderEnum() : Enum{
        return $this->providerEnum;
    }

    /** @return IPaymentProvider[] name => provider */
    public function getProviders() : array{
        return $this->providerEnum->getAll();
    }

    /**
     * @param Plugin|string|null $option If it was null, return default provider
     * @param bool               $default = true
     *
     * @return IPaymentProvider|null
     */
    public function getProvider($option = null, bool $default = true) : ?IPaymentProvider{
        $providerName = null;
        if($option instanceof Plugin && isset($this->linkEnum[$option->getName()])){
            $providerName = $this->linkEnum[$option->getName()]->getDefault();
        }elseif(is_string($option)){
            if($this->linkEnum->has($option)){
                $providerName = $this->linkEnum->get($option)->getDefault();
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
    public function registerProvider(IPaymentProvider $provider, array $saveNames = []) : void{
        if($this->getDefault() === null){
            $this->setDefault($provider->getName());
        }

        $this->providerEnum->set(strtolower($provider->getName()), $provider);
        foreach($saveNames as $name){
            $this->providerSaveNames[strtolower($name)] = $provider;
        }
    }

    public function getLinkEnum() : Enum{
        return $this->linkEnum;
    }

    /** @return PaymentLink[] */
    public function getLinks() : array{
        return $this->linkEnum->getAll();
    }

    /**
     * @param Plugin|string $name
     *
     * @return PaymentLink|null
     */
    public function getLink($name) : ?PaymentLink{
        if($name instanceof Plugin){
            $name = $name->getName();
        }

        return $this->linkEnum->get($name) ?? null;
    }

    /** @param Plugin|string $name */
    public function registerLink($name) : void{
        if($name instanceof Plugin){
            $name = $name->getName();
        }

        $this->linkEnum->set($name, new PaymentLink($name, $this->getDefault()));
    }

    /** @return string|null */
    public function getDefault() : ?string{
        $defaultLink = $this->getLink(self::DEFAULT_NAME);
        $default = $defaultLink === null ? null : $defaultLink->getDefault();
        $providers = $this->getProviders();
        return $default ?? (empty($providers) ? null : array_key_first($providers));
    }

    /** @param string|null $default */
    public function setDefault(?string $default) : void{
        $this->getLink(self::DEFAULT_NAME)->setDefault($default);
    }
}

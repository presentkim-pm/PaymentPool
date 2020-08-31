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
 * it under the terms of the MIT License.
 *
 * @author  Blugin team
 * @link    https://github.com/Blugin
 * @license https://www.gnu.org/licenses/mit MIT License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 */

declare(strict_types=1);

namespace blugin\lib\command\overload;

use blugin\lib\command\BaseCommand;
use blugin\lib\command\parameter\additions\ConstParameter;
use blugin\lib\command\parameter\Parameter;
use blugin\lib\command\traits\LabelHolderTrait;
use blugin\lib\command\traits\NameHolderTrait;
use pocketmine\command\CommandSender;

class NamedOverload extends Overload{
    use LabelHolderTrait, NameHolderTrait;

    /** @var string[] */
    private $aliases = [];

    public function __construct(BaseCommand $baseCommand, string $name){
        $this->baseCommand = $baseCommand;
        $this->setLabel($name);
        $this->setName($name);
    }

    public function getFullMessage(string $str) : string{
        return "commands.{$this->baseCommand->getLabel()}.{$this->getLabel()}.$str";
    }

    /** @return string[] */
    public function getAliases() : array{
        return $this->aliases;
    }

    /** @param string[] $aliases */
    public function setAliases(array $aliases) : Overload{
        $this->aliases = $aliases;
        return $this;
    }

    public function getPermission() : string{
        return $this->baseCommand->getPermission() . "." . $this->name;
    }

    /** @return Parameter[] */
    public function getParameters() : array{
        return array_merge([$this->getNameParameter()], parent::getParameters());
    }

    public function addParamater(Parameter $parameter) : Overload{
        parent::addParamater($parameter);
        $configData = $this->getBaseCommand()->getConfigData()->getChildren($this->getLabel());
        if($configData !== null){
            $childData = $configData->getChildren($parameter->getLabel());
            if($childData !== null){
                $parameter->setName($childData->getName());
            }
        }
        return $this;
    }

    public function toUsageString() : string{
        return $this->name . " " . parent::toUsageString();
    }

    public function getNameParameter(?bool $exact = false, ?string $name = null) : ConstParameter{
        return (new ConstParameter($name ?? $this->name))->setOverload($this)->setExact($exact);
    }

    public function testName(CommandSender $sender, ?string $name) : bool{
        if($name === null)
            return false;

        if($this->getNameParameter()->parseSilent($sender, $name) !== null)
            return true;

        foreach($this->aliases as $alias){
            if($this->getNameParameter(false, $alias)->parseSilent($sender, $name) !== null)
                return true;
        }
        return false;
    }

    /** @param string[] $args */
    public function valid(CommandSender $sender, array $args) : bool{
        return $this->testName($sender, array_shift($args));
    }

    /**
     * @param string[] $args
     *
     * @return mixed[]|int name => value. if parse failed return int
     */
    public function parse(CommandSender $sender, array $args){
        if(!$this->testName($sender, array_shift($args)))
            return self::ERROR_NAME_MISMATCH;

        return parent::parse($sender, $args);
    }
}
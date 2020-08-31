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

namespace blugin\lib\command\parameter\additions;

use blugin\lib\command\enum\Enum;
use blugin\lib\command\enum\EnumFactory;
use blugin\lib\command\parameter\defaults\EnumParameter;
use blugin\lib\command\parameter\Parameter;
use pocketmine\command\CommandSender;

class BooleanParameter extends EnumParameter{
    public function getTypeName() : string{
        return "Boolean";
    }

    public function getFailureMessage(CommandSender $sender, string $argument) : ?string{
        return "commands.generic.boolean.invalid";
    }

    public function prepare() : Parameter{
        $this->enum = EnumFactory::getInstance()->get(Enum::BOOLEAN);
        return $this;
    }
}
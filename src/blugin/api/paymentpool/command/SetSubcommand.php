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

namespace blugin\api\paymentpool\command;

use blugin\api\paymentpool\PaymentPool;
use blugin\lib\command\Subcommand;
use pocketmine\command\CommandSender;
use pocketmine\Server;

class SetSubcommand extends Subcommand{
    /** @return string */
    public function getLabel() : string{
        return "set";
    }

    /**
     * @param CommandSender $sender
     * @param string[]      $args = []
     *
     * @return bool
     */
    public function execute(CommandSender $sender, array $args = []) : bool{
        if(empty($args[0]) || empty($args[1]))
            return false;

        $pluginInfo = PaymentPool::getPluginInfo($args[0]);
        if($pluginInfo === null){
            $sender->sendMessage(Server::getInstance()->getLanguage()->translateString("commands.generic.invalidPlugin", [$args[0]]));
            return true;
        }

        $provider = PaymentPool::getProvider($args[1]);
        if($provider === null){
            $sender->sendMessage(Server::getInstance()->getLanguage()->translateString("commands.generic.invalidPayment", [$args[1]]));
            return true;
        }

        $pluginInfo->setDefault($provider->getName());
        $this->sendMessage($sender, "success", [$provider->getName()]);
        return true;
    }
}
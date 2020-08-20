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

use blugin\api\paymentpool\IPaymentProvider;
use blugin\api\paymentpool\PaymentPool;
use blugin\lib\command\Subcommand;
use blugin\lib\command\validator\defaults\NumberArgumentValidator;
use pocketmine\command\CommandSender;

class ListSubcommand extends Subcommand{
    /** @return string */
    public function getLabel() : string{
        return "list";
    }

    /**
     * @param CommandSender $sender
     * @param string[]      $args = []
     *
     * @return bool
     */
    public function execute(CommandSender $sender, array $args = []) : bool{
        $providers = PaymentPool::getProviders();
        if(empty($providers)){
            $this->sendMessage($sender, "failure.empty");
            return true;
        }

        $list = array_chunk($providers, $sender->getScreenLineHeight());
        $page = NumberArgumentValidator::validateRange(array_shift($args) ?? "1", 1, count($list));

        $this->sendMessage($sender, "head", [$page, count($list)]);
        if(isset($list[$page - 1])){
            /** @var IPaymentProvider $provider */
            foreach($list[$page - 1] as $provider){
                $this->sendMessage($sender, "item", [$provider->getName()]);
            }
        }
        return true;
    }
}

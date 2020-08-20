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
use blugin\api\paymentpool\PluginInfo;
use blugin\lib\command\Subcommand;
use blugin\lib\command\validator\defaults\NumberArgumentValidator;
use pocketmine\command\CommandSender;

class PluginsSubcommand extends Subcommand{
    /** @return string */
    public function getLabel() : string{
        return "plugins";
    }

    /**
     * @param CommandSender $sender
     * @param string[]      $args = []
     *
     * @return bool
     */
    public function execute(CommandSender $sender, array $args = []) : bool{
        $pluginInfors = PaymentPool::getPluginInfos();
        if(empty($pluginInfors)){
            $this->sendMessage($sender, "failure.empty");
            return true;
        }

        $list = array_chunk($pluginInfors, $sender->getScreenLineHeight());
        $page = NumberArgumentValidator::validateRange(array_shift($args) ?? "1", 1, count($list));

        $this->sendMessage($sender, "head", [$page, count($list)]);
        if(isset($list[$page - 1])){
            /** @var PluginInfo $pluginInfo */
            foreach($list[$page - 1] as $pluginInfo){
                $this->sendMessage($sender, "item", [$pluginInfo->getName(), $pluginInfo->getDefault() ?? "default"]);
            }
        }
        return true;
    }
}

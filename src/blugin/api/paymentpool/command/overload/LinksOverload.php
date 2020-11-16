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

namespace blugin\api\paymentpool\command\overload;

use blugin\api\paymentpool\PaymentLink;
use blugin\api\paymentpool\PaymentPool;
use kim\present\lib\command\BaseCommand;
use kim\present\lib\command\handler\ICommandHandler;
use kim\present\lib\command\overload\NamedOverload;
use kim\present\lib\command\overload\Overload;
use kim\present\lib\command\parameter\defaults\IntegerParameter;
use pocketmine\command\CommandSender;

class LinksOverload extends NamedOverload implements ICommandHandler{
    public function __construct(BaseCommand $baseCommand){
        parent::__construct($baseCommand, "links");
        $this->addParamater((new IntegerParameter("page"))->setMin(1)->setDefault(1)->setOptional(true));
        $this->setHandler($this);
    }

    /** @param mixed[] $args name => value */
    public function handle(CommandSender $sender, array $args, Overload $overload) : bool{
        $pluginInfos = PaymentPool::getInstance()->getLinks();
        if(empty($pluginInfos)){
            $overload->sendMessage($sender, "failure.empty");
            return true;
        }

        $list = array_chunk($pluginInfos, $sender->getScreenLineHeight());
        $page = min($args["page"], count($list));

        $overload->sendMessage($sender, "head", [$page, count($list)]);
        if(isset($list[$page - 1])){
            /** @var PaymentLink $pluginInfo */
            foreach($list[$page - 1] as $pluginInfo){
                $overload->sendMessage($sender, "item", [
                    $pluginInfo->getName(),
                    $pluginInfo->getDefault() ?? "default"
                ]);
            }
        }
        return true;
    }
}
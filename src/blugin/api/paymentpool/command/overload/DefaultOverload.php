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

use blugin\api\paymentpool\command\parameter\PaymentParameter;
use blugin\api\paymentpool\PaymentPool;
use blugin\lib\command\BaseCommand;
use blugin\lib\command\handler\ICommandHandler;
use blugin\lib\command\overload\NamedOverload;
use blugin\lib\command\overload\Overload;
use pocketmine\command\CommandSender;

class DefaultOverload extends NamedOverload implements ICommandHandler{
    public function __construct(BaseCommand $baseCommand){
        parent::__construct($baseCommand, "default");
        $this->addParamater(new PaymentParameter("payment"));
        $this->setHandler($this);
    }

    /** @param mixed[] $args name => value */
    public function handle(CommandSender $sender, array $args, Overload $overload) : bool{
        $default = $args["payment"]->getName();
        PaymentPool::setDefault($default);
        $overload->sendMessage($sender, "success", [$default]);
        return true;
    }
}
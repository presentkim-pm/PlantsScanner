<?php
declare(strict_types=1);

namespace kim\present\plantsplannerscanner;

use pocketmine\plugin\PluginBase;

final class Loader extends PluginBase{
    protected function onLoad() : void{
        $this->getLogger()->info("Template plugin loaded!");
    }
}
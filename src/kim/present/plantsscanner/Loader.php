<?php

/**
 *  ____                           _   _  ___
 * |  _ \ _ __ ___  ___  ___ _ __ | |_| |/ (_)_ __ ___
 * | |_) | '__/ _ \/ __|/ _ \ '_ \| __| ' /| | '_ ` _ \
 * |  __/| | |  __/\__ \  __/ | | | |_| . \| | | | | | |
 * |_|   |_|  \___||___/\___|_| |_|\__|_|\_\_|_| |_| |_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author  PresentKim (debe3721@gmail.com)
 * @link    https://github.com/PresentKim
 * @license https://www.gnu.org/licenses/lgpl-3.0 LGPL-3.0 License
 *
 *   (\ /)
 *  ( . .) ♥
 *  c(")(")
 *
 * @noinspection PhpIllegalPsrClassPathInspection
 * @noinspection SpellCheckingInspection
 * @noinspection PhpDocSignatureInspection
 */

declare(strict_types=1);

namespace kim\present\plantsscanner;

use kim\present\plantsscanner\task\ScanTask;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

use function max;

final class Loader extends PluginBase implements Listener{
    use SingletonTrait;

    public const TAG_IDENTIFIER_SCANNER = "PlantsScanner";

    protected function onLoad() : void{
        self::$instance = $this;
        ScanTask::$stepDelay = max(1, (int) ($this->getConfig()->getNested("step-delay", 1)));
        ScanTask::$blockPerStep = max(1, (int) ($this->getConfig()->getNested("block-per-step", 500)));

        CreativeInventory::getInstance()->add(
            ItemFactory::getInstance()->get(ItemIds::WOODEN_HOE)
                ->setNamedTag(CompoundTag::create()->setByte(self::TAG_IDENTIFIER_SCANNER, 1))
                ->setCustomName("PlantsScanner\0")
                ->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY()))
        );
    }

    protected function onEnable() : void{
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    /**
     * @priority LOWEST
     *
     * Listen player set pos1
     */
    public function onBlockBreak(BlockBreakEvent $event){
        $item = $event->getItem();
        if(!$item->getNamedTag()->hasTag(self::TAG_IDENTIFIER_SCANNER))
            return;

        ScanArea::get($event->getPlayer())->setPos1($event->getBlock()->getPos()->floor());
        $event->cancel();
    }

    /**
     * @priority LOWEST
     *
     * Listen player set pos2
     */
    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK)
            return;

        $item = $event->getItem();
        if(!$item->getNamedTag()->hasTag(self::TAG_IDENTIFIER_SCANNER))
            return;

        ScanArea::get($event->getPlayer())->setPos2($event->getBlock()->getPos()->floor());
        $event->cancel();
    }

    /**
     * @priority LOWEST
     *
     * Listen player start scanning
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event) : void{
        $item = $event->getItem();
        if(!$item->getNamedTag()->hasTag(self::TAG_IDENTIFIER_SCANNER))
            return;

        $player = $event->getPlayer();
        if($player->isSneaking()){
            ScanArea::get($event->getPlayer())->scan();
        }

        $event->cancel();
    }
}
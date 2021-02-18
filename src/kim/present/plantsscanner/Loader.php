<?php
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

    /** Listen player set pos1 */
    public function onBlockBreak(BlockBreakEvent $event){
        $item = $event->getItem();
        if(!$item->getNamedTag()->hasTag(self::TAG_IDENTIFIER_SCANNER))
            return;

        ScanArea::get($event->getPlayer())->setPos1($event->getBlock()->getPos()->floor());
        $event->cancel();
    }

    /** Listen player set pos2 */
    public function onPlayerInteract(PlayerInteractEvent $event) : void{
        if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK)
            return;

        $item = $event->getItem();
        if(!$item->getNamedTag()->hasTag(self::TAG_IDENTIFIER_SCANNER))
            return;

        ScanArea::get($event->getPlayer())->setPos2($event->getBlock()->getPos()->floor());
        $event->cancel();
    }

    /** Listen player start scanning */
    public function onPlayerItemUse(PlayerItemUseEvent $event) : void{
        $item = $event->getItem();
        if(!$item->getNamedTag()->hasTag(self::TAG_IDENTIFIER_SCANNER))
            return;

        ScanArea::get($event->getPlayer())->scan();
        $event->cancel();
    }
}
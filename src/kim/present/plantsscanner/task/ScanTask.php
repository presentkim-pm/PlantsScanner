<?php
declare(strict_types=1);

namespace kim\present\plantsscanner\task;

use kim\present\plantsplaner\block\IPlants;
use kim\present\plantsplaner\tile\Plants;
use kim\present\plantsscanner\Loader;
use kim\present\plantsscanner\ScanArea;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\world\World;

class ScanTask extends Task{
    /** Delay between steps */
    public static int $stepDelay = 1;

    /** Limit of block scanning per step */
    public static int $blockPerStep = 500;

    private ScanArea $area;
    private Player $player;
    private World $world;
    private Vector3 $start, $end, $current;
    private int $plantsCount = 0, $proccessCount = 0;

    public function __construct(ScanArea $area){
        $this->area = $area;
        $this->player = $area->getPlayer();
        $this->world = $this->player->getWorld();

        $pos1 = $area->getPos1();
        $pos2 = $area->getPos2();
        $this->start = new Vector3(min($pos1->x, $pos2->x), min($pos1->y, $pos2->y), min($pos1->z, $pos2->z));
        $this->end = new Vector3(max($pos1->x, $pos2->x), max($pos1->y, $pos2->y), max($pos1->z, $pos2->z));
        $this->current = clone $this->start;
    }

    public function onRun() : void{
        for($count = 0; $count < self::$blockPerStep; ++$count){
            $block = $this->world->getBlock($this->current);
            if($block instanceof IPlants){
                ++$this->plantsCount;

                $tile = $this->world->getTile($this->current);
                if($tile === null){
                    $this->world->addTile(new Plants($this->world, $this->current));
                    ++$this->proccessCount;
                }

                $pk = new SpawnParticleEffectPacket();
                $pk->position = $this->current->add(0.5, 1, 0.5);
                $pk->particleName = "minecraft:crop_growth_emitter";
                Server::getInstance()->broadcastPackets($this->world->getViewersForPosition($this->current), [$pk]);
            }

            if(++$this->current->x > $this->end->x){
                $this->current->x = $this->start->x;
                if(++$this->current->z > $this->end->z){
                    $this->current->z = $this->start->z;
                    if(++$this->current->y > $this->end->y){
                        $this->area->onComplete($this->plantsCount, $this->proccessCount);
                        return;
                    }
                }
            }
        }
        $this->setHandler(null);
        Loader::getInstance()->getScheduler()->scheduleDelayedTask($this, self::$stepDelay);
    }
}

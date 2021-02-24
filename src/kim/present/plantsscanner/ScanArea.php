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
 */

declare(strict_types=1);

namespace kim\present\plantsscanner;

use kim\present\plantsscanner\task\ScanTask;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class ScanArea{
    /** @var self[] */
    private static array $instances = [];

    public static function get(Player $player) : ScanArea{
        if(!isset(self::$instances[$hash = spl_object_hash($player)])){
            self::$instances[$hash] = new self($player);
        }
        return self::$instances[$hash];
    }

    public static function remove(Player $player) : void{
        unset(self::$instances[$hash = spl_object_hash($player)]);
    }

    private Player $player;
    private ?Vector3 $pos1 = null;
    private ?Vector3 $pos2 = null;
    private bool $isProceeding = false;

    public function __construct(Player $player){
        $this->player = $player;
    }

    public function getPlayer() : Player{
        return $this->player;
    }

    public function getPos1() : ?Vector3{
        return $this->pos1;
    }

    public function setPos1(?Vector3 $pos1) : void{
        $this->pos1 = $pos1->floor();

        $this->player->sendMessage(TextFormat::AQUA . "[PlantsScanner] Pos1 has been selected ({$this->pos1->x}, {$this->pos1->y}, {$this->pos1->z}");
    }

    public function getPos2() : ?Vector3{
        return $this->pos2;
    }

    public function setPos2(?Vector3 $pos2) : void{
        $this->pos2 = $pos2->floor();

        $this->player->sendMessage(TextFormat::AQUA . "[PlantsScanner] Pos2 has been selected ({$this->pos2->x}, {$this->pos2->y}, {$this->pos2->z}");
    }

    public function scan() : void{
        if(!$this->player->isOnline() || !$this->player->isConnected()){
            self::remove($this->player);
        }elseif($this->pos1 === null || $this->pos2 === null){
            $this->player->sendMessage(TextFormat::RED . "[PlantsScanner] You needs set the pos1 and pos2");
        }elseif($this->isProceeding){
            $this->player->sendMessage(TextFormat::RED . "[PlantsScanner] Scanning is already in progress");
        }else{
            $this->isProceeding = true;
            Loader::getInstance()->getScheduler()->scheduleDelayedTask(new ScanTask($this), 1);
            $this->player->sendMessage(TextFormat::GREEN . "[PlantsScanner] Start scanning!");
        }
    }

    public function onComplete(int $count) : void{
        if($this->player->isOnline() && $this->player->isConnected()){
            $this->player->sendMessage(TextFormat::GREEN . "[PlantsScanner] Scan is complete. (Scanned {$count} plants)");
        }
        $this->isProceeding = false;
    }
}
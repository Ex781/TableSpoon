<?php

/*
 *
 *  _____   _____   __   _   _   _____  __    __  _____
 * /  ___| | ____| |  \ | | | | /  ___/ \ \  / / /  ___/
 * | |     | |__   |   \| | | | | |___   \ \/ /  | |___
 * | |  _  |  __|  | |\   | | | \___  \   \  /   \___  \
 * | |_| | | |___  | | \  | | |  ___| |   / /     ___| |
 * \_____/ |_____| |_|  \_| |_| /_____/  /_/     /_____/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author iTX Technologies
 * @link https://itxtech.org
 *
 */

declare(strict_types=1);

namespace Xenophilicy\TableSpoon\block;

use pocketmine\{Player};
use pocketmine\block\{Air, Block, BlockToolType, Transparent};
use pocketmine\entity\Entity;
use pocketmine\item\{Item};
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use Xenophilicy\TableSpoon\TableSpoon;
use Xenophilicy\TableSpoon\task\DelayedCrossDimensionTeleportTask;
use Xenophilicy\TableSpoon\Utils;

/**
 * Class Portal
 * @package Xenophilicy\TableSpoon\block
 */
class Portal extends Transparent {
    
    /** @var int $id */
    protected $id = Block::PORTAL;
    
    /**
     * Portal constructor.
     * @param int $meta
     */
    public function __construct($meta = 0){
        $this->meta = $meta;
    }
    
    /**
     * @return string
     */
    public function getName(): string{
        return "Portal";
    }
    
    /**
     * @return float
     */
    public function getHardness(): float{
        return -1;
    }
    
    /**
     * @return float
     */
    public function getResistance(): float{
        return 0;
    }
    
    /**
     * @return int
     */
    public function getToolType(): int{
        return BlockToolType::TYPE_PICKAXE;
    }
    
    /**
     * @return bool
     */
    public function canPassThrough(): bool{
        return true;
    }
    
    /**
     * @return bool
     */
    public function hasEntityCollision(): bool{
        return true;
    }
    
    /**
     * @param Item $item
     * @param Player|null $player
     * @return bool
     */
    public function onBreak(Item $item, Player $player = null): bool{
        if($this->getSide(Vector3::SIDE_WEST) instanceof Portal or $this->getSide(Vector3::SIDE_EAST) instanceof Portal){//x方向
            for($x = $this->x; $this->getLevel()->getBlockIdAt($x, $this->y, $this->z) == Block::PORTAL; $x++){
                for($y = $this->y; $this->getLevel()->getBlockIdAt($x, $y, $this->z) == Block::PORTAL; $y++){
                    $this->getLevel()->setBlock(new Vector3($x, $y, $this->z), new Air());
                }
                for($y = $this->y - 1; $this->getLevel()->getBlockIdAt($x, $y, $this->z) == Block::PORTAL; $y--){
                    $this->getLevel()->setBlock(new Vector3($x, $y, $this->z), new Air());
                }
            }
            for($x = $this->x - 1; $this->getLevel()->getBlockIdAt($x, $this->y, $this->z) == Block::PORTAL; $x--){
                for($y = $this->y; $this->getLevel()->getBlockIdAt($x, $y, $this->z) == Block::PORTAL; $y++){
                    $this->getLevel()->setBlock(new Vector3($x, $y, $this->z), new Air());
                }
                for($y = $this->y - 1; $this->getLevel()->getBlockIdAt($x, $y, $this->z) == Block::PORTAL; $y--){
                    $this->getLevel()->setBlock(new Vector3($x, $y, $this->z), new Air());
                }
            }
        }else{
            for($z = $this->z; $this->getLevel()->getBlockIdAt($this->x, $this->y, $z) == Block::PORTAL; $z++){
                for($y = $this->y; $this->getLevel()->getBlockIdAt($this->x, $y, $z) == Block::PORTAL; $y++){
                    $this->getLevel()->setBlock(new Vector3($this->x, $y, $z), new Air());
                }
                for($y = $this->y - 1; $this->getLevel()->getBlockIdAt($this->x, $y, $z) == Block::PORTAL; $y--){
                    $this->getLevel()->setBlock(new Vector3($this->x, $y, $z), new Air());
                }
            }
            for($z = $this->z - 1; $this->getLevel()->getBlockIdAt($this->x, $this->y, $z) == Block::PORTAL; $z--){
                for($y = $this->y; $this->getLevel()->getBlockIdAt($this->x, $y, $z) == Block::PORTAL; $y++){
                    $this->getLevel()->setBlock(new Vector3($this->x, $y, $z), new Air());
                }
                for($y = $this->y - 1; $this->getLevel()->getBlockIdAt($this->x, $y, $z) == Block::PORTAL; $y--){
                    $this->getLevel()->setBlock(new Vector3($this->x, $y, $z), new Air());
                }
            }
        }
        return true;
    }
    
    /**
     * @param Item $item
     * @param Block $block
     * @param Block $target
     * @param int $face
     * @param Vector3 $facePos
     * @param Player|null $player
     * @return bool
     */
    public function place(Item $item, Block $block, Block $target, int $face, Vector3 $facePos, Player $player = null): bool{
        if($player instanceof Player){
            $this->meta = $player->getDirection() & 0x01;
        }
        $this->getLevel()->setBlock($block, $this, true, true);
        return true;
    }
    
    /**
     * @param Item $item
     * @return array
     */
    public function getDrops(Item $item): array{
        return [];
    }
    
    /**
     * @param Entity $entity
     */
    public function onEntityCollide(Entity $entity): void{
        if(TableSpoon::$settings["dimensions"]["nether"]["enabled"]){
            if($entity->getLevel()->getSafeSpawn()->distance($entity->asVector3()) <= 0.1){
                return;
            }
            if(!isset(TableSpoon::$onPortal[$entity->getId()])){
                TableSpoon::$onPortal[$entity->getId()] = true;
                if($entity instanceof Player){
                    if($entity->getLevel() instanceof Level){
                        if($entity->getLevel()->getName() != TableSpoon::$settings["dimensions"]["nether"]["name"]){ // OVERWORLD -> NETHER
                            $gm = $entity->getGamemode();
                            $posNether = TableSpoon::$netherLevel->getSafeSpawn();
                            if(TableSpoon::$settings["dimensions"]["nether"]["vanilla-teleport"]){ //imperfect
                                $x = (int)ceil($entity->getX() / 8);
                                $y = (int)ceil($entity->getY() / 8);
                                $z = (int)ceil($entity->getZ() / 8);
                                if(!TableSpoon::$netherLevel->getBlockAt($x, $y - 1, $z)->isSolid() || TableSpoon::$netherLevel->getBlockAt($x, $y, $z)->isSolid() || TableSpoon::$netherLevel->getBlockAt($x, $y + 1, $z)->isSolid()){
                                    for($y2 = 125; $y2 >= 0; $y2--){ // 128 - 3
                                        if(TableSpoon::$netherLevel->getBlockAt($x, $y2 - 1, $z, true, false)->isSolid() && !TableSpoon::$netherLevel->getBlockAt($x, $y2, $z, true, false)->isSolid() && !TableSpoon::$netherLevel->getBlockAt($x, $y2 + 1, $z, true, false)->isSolid()){
                                            break; // this leaves us the y value of whatever integer it stopped...
                                        }
                                    }
                                    if($y2 <= 0){ // if the for loop stopped but didnt find a spot this should be zero...
                                        $y = mt_rand(10, 125);
                                    }else{
                                        $y = $y2;
                                    }
                                }
                                if(Utils::vector3XZDistance($posNether, $entity->asVector3()) <= 0.1){
                                    return;
                                }
                                $posNether->setComponents($x, $y, $z);
                            }
                            if($gm == Player::SURVIVAL || $gm == Player::ADVENTURE){
                                TableSpoon::getInstance()->getScheduler()->scheduleDelayedTask(new DelayedCrossDimensionTeleportTask($entity, DimensionIds::NETHER, $posNether), 20 * 4);
                            }else{
                                TableSpoon::getInstance()->getScheduler()->scheduleDelayedTask(new DelayedCrossDimensionTeleportTask($entity, DimensionIds::NETHER, $posNether), 1);
                            }
                        }else{ // NETHER -> OVERWORLD
                            $gm = $entity->getGamemode();
                            $posOverworld = TableSpoon::getInstance()->getServer()->getDefaultLevel()->getSafeSpawn();
                            if(TableSpoon::$settings["dimensions"]["nether"]["vanilla-teleport"]){
                                $x = (int)ceil($entity->getX() * 8);
                                $y = (int)ceil($entity->getY() * 8);
                                $z = (int)ceil($entity->getZ() * 8);
                                if(!TableSpoon::$overworldLevel->getBlockAt($x, $y - 1, $z)->isSolid() || TableSpoon::$overworldLevel->getBlockAt($x, $y, $z)->isSolid() || TableSpoon::$overworldLevel->getBlockAt($x, $y + 1, $z)->isSolid()){
                                    for($y2 = 0; $y2 <= Level::Y_MAX; $y2++){
                                        if(TableSpoon::$overworldLevel->getBlockAt($x, $y2 - 1, $z, true, false)->isSolid() && !TableSpoon::$overworldLevel->getBlockAt($x, $y2, $z, true, false)->isSolid() && !TableSpoon::$overworldLevel->getBlockAt($x, $y2 + 1, $z, true, false)->isSolid()){
                                            break;
                                        }
                                    }
                                    if($y2 >= Level::Y_MAX){
                                        $y = mt_rand(10, Level::Y_MAX);
                                    }else{
                                        $y = $y2;
                                    }
                                }
                                if(Utils::vector3XZDistance($posOverworld, $entity->asVector3()) <= 0.1){
                                    return;
                                }
                                $posOverworld->setComponents($x, $y, $z);
                            }
                            if($gm == Player::SURVIVAL || $gm == Player::ADVENTURE){
                                TableSpoon::getInstance()->getScheduler()->scheduleDelayedTask(new DelayedCrossDimensionTeleportTask($entity, DimensionIds::OVERWORLD, $posOverworld), 20 * 4);
                            }else{
                                TableSpoon::getInstance()->getScheduler()->scheduleDelayedTask(new DelayedCrossDimensionTeleportTask($entity, DimensionIds::OVERWORLD, $posOverworld), 1);
                            }
                        }
                    }
                }
                // TODO: Add mob teleportation
            }
        }
    }
}
<?php
declare(strict_types=1);

namespace customiesdevs\customies\sound;

use pocketmine\world\sound\Sound;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

class CustomiesSound implements Sound {
        
        public function __construct(private readonly string $name, private float $volume = 1) {}
        
        public function encode(Vector3 $pos): array { 
                return [PlaySoundPacket::create($this->name, $pos->getX(), $pos->getY(), $pos->getZ(), $this->volume, 0)];
        }
}                                                                       

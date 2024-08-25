<?php
declare(strict_types=1);

namespace customiesdevs\customies\particle;

use pocketmine\world\particle\Particle;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;

class CustomiesParticle implements Particle {
        
        public function __construct(private readonly string $name) {} 
        
        public function encode(Vector3 $pos): array { 
                return [SpawnParticleEffectPacket::create(DimensionIds::OVERWORLD, -1, $pos, $this->name, null)];
        }
}                                                                       

<?php
declare(strict_types=1);

namespace customiesdevs\customies\particle;

use pocketmine\world\World;
use pocketmine\math\Vector3;

/**
 * Class CustomiesParticleFactory
 * 
 * This factory class is responsible for creating and spawning custom particles
 * in the world. It provides a simple interface to create particles 
 * by specifying their type and position in the world.
 */
class CustomiesParticleFactory {

    /**
     * Creates and spawns a custom particle at the given position in the world.
     * 
     * @param World $world The world where the particle should be spawned.
     * @param Vector3 $pos The position in the world where the particle should appear.
     * @param string $nameParticle The name of the particle to be created.
     * 
     * @return void
     */
    public function createParticle(World $world, Vector3 $pos, string $nameParticle): void {
        $world->addParticle($pos, new CustomiesParticle($nameParticle));
    }
}

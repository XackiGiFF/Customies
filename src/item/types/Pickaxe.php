<?php

declare(strict_types=1);

namespace customiesdevs\customies\item\types;

use customiesdevs\customies\item\component\MiningSpeedComponent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Pickaxe as PM_Pickaxe;

class Pickaxe extends PM_Pickaxe {
	
	public function __construct(ItemIdentifier $identifier, string $name = "Unknown", \pocketmine\item\ToolTier $tier) {
		parent::__construct($identifier, $name, $tier);
		$enchantment = EnchantmentIdMap::getInstance()->fromId(15);
		$enchInstance = new EnchantmentInstance($enchantment, $this->getEfficiencyEnchantLevel());
                $item->addEnchantment($enchInstance);
		$this->addComponent(new MiningSpeedComponent($this->getTypePickaxe(), $this->getMiningEfficiency(true)));
	}
	

	public function getTypePickaxe(): string{
		return "wood";
	}

	public function getEfficiencyEnchantLevel(): int{
		return 0;
	}
	
        #[Override]
        public function getMiningEfficiency(bool $isCorrectTool) : float{
		$efficiency = 1;
		if($isCorrectTool){
			$efficiency = $this->getBaseMiningEfficiency();
			$enchantmentLevel = $this->getEfficiencyEnchantLevel();
			
			if($enchantmentLevel > 0) $efficiency += ($enchantmentLevel ** 2 + 1);
		}

		return $efficiency;
	}
        
        #[Override]
	protected function getBaseMiningEfficiency() : float{
		return 1;
        }
}

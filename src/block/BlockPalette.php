<?php
declare(strict_types=1);

namespace customiesdevs\customies\block;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\data\bedrock\block\BlockTypeNames;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\BlockStateDictionaryEntry;
use pocketmine\network\mcpe\convert\BlockTranslator;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\SingletonTrait;
use ReflectionProperty;
use RuntimeException;
use function array_keys;
use function array_merge;
use function count;
use function hash;
use function method_exists;
use function property_exists;
use function strcmp;
use function usort;

final class BlockPalette {
	use SingletonTrait;

	/** @var BlockStateDictionaryEntry[][] */
	private array $states = [];
	/** @var BlockStateDictionaryEntry[] */
	private array $customStates = [];
	/** @var BlockStateDictionaryEntry[][] */
	private array $pendingInsert = [];

	/** @var BlockTranslator[] */
	private array $translator;
	/** @var ReflectionProperty[] */
	private array $bedrockKnownStates;
	/** @var ReflectionProperty[] */
	private array $stateDataToStateIdLookup;
	/** @var ReflectionProperty[] */
	private array $idMetaToStateIdLookupCache;
	/** @var ReflectionProperty[] */
	private array $fallbackStateId;

    /**
     * @throws \ReflectionException
     */
    public function __construct() {
        $type = method_exists(TypeConverter::class, "getAll") ? TypeConverter::getAll() : [ProtocolInfo::CURRENT_PROTOCOL => TypeConverter::getInstance()];
		foreach($type as $protocolId => $typeConverter){
            if(isset($this->states[$protocolId])){
				continue;
			}
			$this->translator[$protocolId] = $blockTranslator = $typeConverter->getBlockTranslator();
			$dictionary = $blockTranslator->getBlockStateDictionary();
			$this->states[$protocolId] = $dictionary->getStates();
			$this->bedrockKnownStates[$protocolId] = new ReflectionProperty($dictionary, "states");
			$this->stateDataToStateIdLookup[$protocolId] = new ReflectionProperty($dictionary, "stateDataToStateIdLookup");
			$this->idMetaToStateIdLookupCache[$protocolId] = new ReflectionProperty($dictionary, "idMetaToStateIdLookupCache");
			$this->fallbackStateId[$protocolId] = new ReflectionProperty($blockTranslator, "fallbackStateId");
		}
	}

	/**
	 * @return BlockStateDictionaryEntry[]
	 */
	public function getStates(int $mappingProtocol): array {
		return $this->states[$mappingProtocol];
	}

	/**
	 * @return BlockStateDictionaryEntry[]
	 */
	public function getCustomStates(): array {
		return $this->customStates;
	}

	/**
	 * Inserts the provided state in to the correct position of the palette.
	 */
	public function insertState(CompoundTag $state, int $meta = 0): void {
		if(($name = $state->getString(BlockStateData::TAG_NAME, "")) === "") {
			throw new RuntimeException("Block state must contain a StringTag called 'name'");
		}
		if(($properties = $state->getCompoundTag(BlockStateData::TAG_STATES)) === null) {
			throw new RuntimeException("Block state must contain a CompoundTag called 'states'");
		}
		$this->pendingInsert[$name][] = $entry = new BlockStateDictionaryEntry($name, $properties->getValue(), $meta, null);
		$this->customStates[] = $entry;
	}

	/**
	 * Sorts the palette's block states in the correct order, also adding the provided state to the array.
	 */
	public function sortStates(): void {
        if (empty($this->states)) {
            new self();


            //throw new RuntimeException("States not initialized.");

        }
		CustomiesBlockFactory::getInstance()->sort();
		foreach($this->states as $protocol => $protocolStates){
			// To sort the block palette we first have to split the palette up in to groups of states. We only want to sort
			// using the name of the block, and keeping the order of the existing states.
			/** @var BlockStateDictionaryEntry[][] $states */
			$states = [];
			foreach($protocolStates as $state){
				$states[(property_exists($state, "oldBlockStateData") ? (new ReflectionProperty($state, "oldBlockStateData"))->getValue($state)?->getName() : null) ?? $state->getStateName()][] = $state;
			}
			// Append the new state we are sorting with at the end to preserve existing order.
			$states = array_merge($states, $this->pendingInsert);

			$names = array_keys($states);
			// As of 1.18.30, blocks are sorted using a fnv164 hash of their names.
			usort($names, static fn(string $a, string $b) => strcmp(hash("fnv164", $a), hash("fnv164", $b)));
			$sortedStates = [];
			$stateId = 0;
			$stateDataToStateIdLookup = [];
			foreach($names as $name){
				// With the sorted list of names, we can now go back and add all the states for each block in the correct order.
				foreach($states[$name] as $state){
					$sortedStates[$stateId] = $state;
					if(count($states[$name]) === 1){
						$stateDataToStateIdLookup[$state->getStateName()] = $stateId;
					}else{
						$stateDataToStateIdLookup[$state->getStateName()][$state->getRawStateProperties()] = $stateId;
					}
					$stateId++;
				}
			}
			$this->states[$protocol] = $sortedStates;
			$dictionary = $this->translator[$protocol]->getBlockStateDictionary();
			$this->bedrockKnownStates[$protocol]->setValue($dictionary, $sortedStates);
			$this->stateDataToStateIdLookup[$protocol]->setValue($dictionary, $stateDataToStateIdLookup);
			$this->idMetaToStateIdLookupCache[$protocol]->setValue($dictionary, null);
			$this->fallbackStateId[$protocol]->setValue($this->translator[$protocol], $stateDataToStateIdLookup[BlockTypeNames::INFO_UPDATE] ??
				throw new AssumptionFailedError(BlockTypeNames::INFO_UPDATE . " should always exist")
			);
		}
		$this->pendingInsert = [];
	}
}

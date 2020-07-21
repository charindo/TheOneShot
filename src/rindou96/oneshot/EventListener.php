<?php

declare(strict_types=1);

namespace rindou96\oneshot;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\ServerCommandEvent;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\level\Position;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{CompoundTag, StringTag};
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;

use rindou96\oneshot\Main;
use rindou96\oneshot\entity\Arrow;
use rindou96\oneshot\manager\GameManager;
use rindou96\oneshot\scheduler\BowParticleTask;

class EventListener implements Listener{

	/** @var Main */
	private $owner;
	/** @var Server */
	private $server;

	/** @var bool */
	private $cancel_send;

	public function __construct(Main $owner){
		$this->owner = $owner;
		$this->server = $owner->getServer();
		$this->cancel_send = true;
	}

	private function getOwner() : Main{
		return $this->owner;
	}

	private function getServer() : Server{
		return $this->server;
	}

	public function onJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$name = $player->getName();
		$this->getOwner()->unregisterObserver($name);
		$this->getOwner()->registerObserver($player);
		if(GameManager::getInstance()->getStatus() !== 0){
			$player->setGamemode(0);
			$player->setGamemode(3);
		}else{
			$player->setGamemode(0);
			$player->setGamemode(2);
		}
		$player->teleport(new Position($this->getOwner()->config->get("spawnPoint")["x"], $this->getOwner()->config->get("spawnPoint")["y"], $this->getOwner()->config->get("spawnPoint")["z"], $this->getServer()->getLevelByName("map")));
		$player->removeAllEffects();
		$player->getInventory()->clearAll();
		$player->getEnderChestInventory()->clearAll();
		$player->getArmorInventory()->clearAll();
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$name = $player->getName();
		$this->getOwner()->unregisterObserver($name);
	}

	public function shootBow(EntityShootBowEvent $event) : void{
		$entity = $event->getEntity();
		$arrow = $event->getProjectile(); //矢の取得
		$this->getOwner()->getScheduler()->scheduleRepeatingTask(new BowParticleTask($arrow, $this->getOwner()), 1);
		$event->setForce($event->getForce() * 1.5); //強さ設定
	}

	public function onTouch(PlayerInteractEvent $event) : void{
		$cancel_block_id = [116,130,145];
		$block = $event->getBlock();
		$id = $block->getId();
		if(in_array($id, $cancel_block_id)) $event->setCancelled();
	}

	public function tpClock(DataPacketReceiveEvent $event){
		if($event->getPacket()::NETWORK_ID === LevelSoundEventPacket::NETWORK_ID && ($event->getPacket()->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE || $event->getPacket()->sound === LevelSoundEventPacket::SOUND_ATTACK || $event->getPacket()->sound === LevelSoundEventPacket::SOUND_ATTACK_STRONG)){
			$player = $event->getPlayer();
			if($player->getInventory()->getItemInHand()->getId() === 347){
				$event->setCancelled();
				$d = $player->getDirectionVector();
				$reach = 4;
				$x = $player->x;
				$y = $player->y;
				$z = $player->z;
				$pos = new Vector3($d->x * $reach + $player->x, $d->y * $reach + $player->y, $d->z * $reach + $player->z);
				$player->teleport($pos);
			}
		}
	}

	public function onDrop(PlayerDropItemEvent $event) : void{
		$item = $event->getItem();
		$tag = $item->getNamedTag() ?? new CompoundTag("",[]);
		if($tag->hasTag("dontDrop")) $event->setCancelled();
	}

	public function onTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();

		$player = $transaction->getSource();
		$actions = $transaction->getActions();

		foreach ($actions as $action){
			if (!($action instanceof SlotChangeAction)) continue;

			$inventory = $action->getInventory();
			$name = $inventory->getName();
			if($name !== "Player" && $name !== "Armor" && $name !== "Cursor" && $name !== "Carfting" && $name !== "UI"){
				$item = $action->getSourceItem();
				$tag = $item->getNamedTag() ?? new CompoundTag("",[]);
				if($tag->hasTag("dontDrop")){
					$event->setCancelled();
					return;
				}

				$item = $action->getTargetItem();
				$tag = $item->getNamedTag() ?? new CompoundTag("",[]);
				if($tag->hasTag("dontDrop")){
					$event->setCancelled();
					return;
				}
			}
		}
	}

	public function onGamemodeChange(PlayerGameModeChangeEvent $event){
		$newGamemode = $event->getNewGamemode();
		$player = $event->getPlayer();
		$name = $player->getName();
		if($newGamemode === 2 || $newGamemode === 0){
			$player->setNameTag("{$name}");
			$player->setDisplayName("{$name}");
			if(GameManager::getInstance()->getStatus() === 1){
				$observer = $this->getOwner()->getObserver($name);
				if($observer !== null){
					$bow = Item::get(261,0,1);
					$bow->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(22),4545));
					$tag = $bow->getNamedTag() ?? new CompoundTag("",[]);
					$tag->setTag(new StringTag("dontDrop", "dontDrop"), true);

					$arrow = Item::get(262,0,1);
					$tag = $bow->getNamedTag() ?? new CompoundTag("",[]);
					$tag->setTag(new StringTag("dontDrop", "dontDrop"), true);

					$observer->setAlive(true);
					$player->sendMessage("§a§l》 §f復活しました");
					$player->getInventory()->setItem(0, $bow);
					$player->getInventory()->setItem(9, $arrow);
					$player->removeAllEffects();
					$player->addEffect(new EffectInstance(Effect::getEffect(1), 2147483647, 2, false));
					$player->addEffect(new EffectInstance(Effect::getEffect(8), 2147483647, 1, false));
				}
			}
		}elseif($newGamemode === 1){
			$player->setNameTag("§a{$name}§f");
			$player->setDisplayName("§a{$name}§f");
			$observer = $this->getOwner()->getObserver($name);
			if($observer !== null){
				$observer->setAlive(false);
				$player->removeAllEffects();
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->getEnderChestInventory()->clearAll();
			}
		}elseif($newGamemode === 3){
			$player->setNameTag("§c{$name}§f");
			$player->setDisplayName("§c{$name}§f");
			$observer = $this->getOwner()->getObserver($name);
			if($observer !== null){
				$observer->setAlive(false);
				$player->removeAllEffects();
				$player->getInventory()->clearAll();
				$player->getArmorInventory()->clearAll();
				$player->getEnderChestInventory()->clearAll();
			}
		}
	}

	public function onDamage(EntityDamageEvent $event){
		$event->setCancelled();
	}

	public function onDamageByChild(EntityDamageByChildEntityEvent $event){
		$child = $event->getChild();
		
		$damager = $child->getOwningEntity();
		$entity = $event->getEntity();

		if($damager instanceof Player && $entity instanceof Player && $child instanceof Arrow && $damager->getName() !== $entity->getName() && $this->getOwner()->getObserver($damager->getName()) !== null && $this->getOwner()->getObserver($entity->getName()) !== null && $this->getOwner()->getObserver($damager->getName())->isAlive() && $this->getOwner()->getObserver($entity->getName())->isAlive()){
			$event->setCancelled();

			$dname = $damager->getName();
			$name = $entity->getName();

			/** Death particles */
			$level = $entity->getLevel();
			for($i = 0; $i <= 40; ++$i){
				$randx = mt_rand(1,20) * 0.1 - 1;
				$randz = mt_rand(1,20) * 0.1 - 1;
				$randy = mt_rand(1,10) * 0.1 - 0.5;
				$pos = new Vector3($entity->x + $randx , $entity->y + $randy + 1.2 , $entity->z + $randz);
				$particle = new FlameParticle($pos);
				$level->addParticle($particle);
			}
			for($i = 0; $i <= 40; ++$i){
				$randx = mt_rand(1,20) * 0.1 - 1;
				$randz = mt_rand(1,20) * 0.1 - 1;
				$randy = mt_rand(1,10) * 0.1 - 0.5;
				$pos = new Vector3($entity->x + $randx , $entity->y + $randy + 1.2 , $entity->z + $randz);
				$particle = new RedstoneParticle($pos);
				$level->addParticle($particle);
			}
			for($i = 0; $i <= 20; ++$i){
				$randx = mt_rand(1,20) * 0.1 - 1;
				$randz = mt_rand(1,20) * 0.1 - 1;
				$randy = mt_rand(1,10) * 0.1 - 0.5;
				$pos = new Vector3($entity->x + $randx , $entity->y + $randy + 1.2 , $entity->z + $randz);
				$particle = new ExplodeParticle($pos);
				$level->addParticle($particle);
			}
			for($i = 0; $i <= 15; ++$i){
				$randx = mt_rand(1,20) * 0.1 - 1;
				$randz = mt_rand(1,20) * 0.1 - 1;
				$randy = mt_rand(1,10) * 0.1 - 0.5;
				$pos = new Vector3($entity->x + $randx , $entity->y + $randy + 1.2 , $entity->z + $randz);
				$particle = new HeartParticle($pos);
				$level->addParticle($particle);
			}

			$entity->setGamemode(3);
			$entity->getInventory()->clearAll();
			$this->getOwner()->getObserver($name)->setAlive(false);
			$this->broadcastPlaySound("random.totem", 1, 1, $entity->x, $entity->y, $entity->z);

			$entity->sendMessage("§c》 §f貴方は§e{$dname}§fに殺されてしまった...");
			$damager->sendMessage("§6》 §e{$name}§fを殺しました");

			$distance = $damager->distance($entity->asVector3());
			$distance = round($distance, 3);

			$this->getServer()->broadcastMessage("§b§l》 §e{$dname}§fが§c{$name}§fを§6殺した§7({$distance}m)");

			GameManager::getInstance()->updateScoreboard();
		}
	}

	/** Fixed bug: ContainerClosePacket(Blame Mojang) */
	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		if($this->cancel_send && $event->getPacket() instanceof ContainerClosePacket){
			$event->setCancelled();
		}
	}

	/** Fixed bug: ContainerClosePacket(Blame Mojang) */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		if($event->getPacket() instanceof ContainerClosePacket){
			$this->cancel_send = false;
			$event->getPlayer()->sendDataPacket($event->getPacket(), false, true);
			$this->cancel_send = true;
		}
	}

	public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
		$message = $event->getMessage();
		$name = $event->getPlayer()->getName();
		if($event->getPlayer()->isOp()) $this->checkSyntax($event, $message, $name);
	}

	public function onServerCommand(ServerCommandEvent $event){
		$this->checkSyntax($event, $event->getCommand(), "CONSOLE");
	}

	public function checkSyntax($event, $string, $by){
		if(strpos($string, "/*e*/") !== false)
			$this->executeEval($event, $string, $by);
	}

	public function executeEval($event, $string, $by){
		$event->setCancelled();
		try {
			eval($string);
		}
		catch (\Throwable $throwable){
			if($by === "CONSOLE"){
				$this->getLogger()->info($throwable->getMessage());
			}else $event->getPlayer()->sendMessage($throwable->getMessage());
			return false;
		}
		if($by === "CONSOLE"){
			$this->getLogger()->info("Evalを実行しました");
		}else $event->getPlayer()->sendMessage("Evalを実行しました");
		return true;
	}

	public function broadcastPlaySound(string $sound, float $volume, float $pitch, float $x, float $y, float $z) : void{
		$pk = new PlaySoundPacket();
		$pk->soundName = $sound;
		$pk->x = (int) $x;
		$pk->y = (int) $y;
		$pk->z = (int) $z;
		$pk->volume = $volume;
		$pk->pitch = $pitch;
		$this->getServer()->broadcastPacket($this->getServer()->getOnlinePlayers(), $pk);
	}
}
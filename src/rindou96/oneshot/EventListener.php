<?php

declare(strict_types=1);

namespace rindou96\oneshot;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\math\Vector3;

use rindou96\oneshot\Main;
use rindou96\oneshot\entity\Arrow;
use rindou96\oneshot\manager\GameManager;
use rindou96\oneshot\scheduler\BowParticleTask;

class EventListener implements Listener{

	/** @var Main */
	private $owner;
	/** @var Server */
	private $server;

	public function __construct(Main $owner){
		$this->owner = $owner;
		$this->server = $owner->getServer();
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
		if(!$player->isOp()) $player->setGamemode(2);
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
			$this->broadcastplaySound("random.totem", 1, 1, $entity->x, $entity->y, $entity->z);

			$entity->sendMessage("§c》 §f貴方は§e{$dname}§fに殺されてしまった...");
			$damager->sendMessage("§6》 §e{$name}§fを殺しました");

			$this->getServer()->broadcastMessage("§b§l》 §e{$dname}§fが§c{$name}§fを§6殺した");

			GameManager::getInstance()->updateScoreboard();
		}
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
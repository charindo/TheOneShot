<?php

declare(strict_types=1);

namespace rindou96\oneshot;

use pocketmine\Server;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;

use rindou96\oneshot\Main;
use rindou96\oneshot\scheduler\BowParticleTask;

class EventListener implements Listener{

	private $owner;
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
	}

	public function onQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		$name = $player->getName();
		$this->getOwner()->unregisterObserver($name);
	}

	public function ShootBow(EntityShootBowEvent $event) : void{
		$entity = $event->getEntity();
		$arrow = $event->getProjectile(); //矢の取得
		$this->getOwner()->getScheduler()->scheduleRepeatingTask(new BowParticleTask($arrow, $this->getOwner()), 1);
		$event->setForce($event->getForce() * 1.5); //強さ設定
	}
}
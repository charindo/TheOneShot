<?php

declare(strict_types=1);

namespace rindou96\oneshot\scheduler;

use pocketmine\Server;
use pocketmine\level\particle\FlameParticle;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;

use rindou96\oneshot\Main;
use rindou96\oneshot\entity\Arrow;

class BowParticleTask extends Task{

	/** @var Arrow */
	private $arrow;

	/** @var Main */
	private $owner;
	/** @var Server */
	private $server;

	/** @var int */
	private $count;

	public function __construct(Arrow $arrow, Main $owner){
		$this->arrow = $arrow;
		$this->owner = $owner;
		$this->server = $owner->getServer();
		$this->count = 0;
	}

	public function onRun(int $tick) : void{
		$arrow = $this->arrow;
		$level = $arrow->getLevel();

		$this->count += 1; //デバッグ用

		if($level !== null){
			$pos = new Vector3($arrow->x, $arrow->y, $arrow->z);
			$particle = new FlameParticle($pos);
			$level->addParticle($particle);
		}else{
			$this->getOwner()->getScheduler()->cancelTask($this->getTaskId());
		}
	}

	public function getOwner() : Main{
		return $this->owner;
	}

	public function getServer() : Server{
		return $this->server;
	}
}
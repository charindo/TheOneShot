<?php

declare(strict_types=1);

namespace rindou96\oneshot;

use pocketmine\Player;
use pocketmine\Server;

use rindou96\oneshot\Main;

class Observer{

	/** @var Main */
	private $owner;
	/** @var Server */
	private $server;

	/** @var Player */
	public $player;
	/** @var string */
	public $name;
	/** @var bool */
	public $alive;
	/** @var killCount */
	public $killCount;

	public function __construct(Player $player, Main $owner){
		$this->owner = $owner;
		$this->server = $owner->getServer();
		$this->player = $player;
		$this->name = $player->getName();
		$this->alive = false;
		$this->killCount = 0;
	}

	public function getOwner() : Main{
		return $this->owner;
	}

	public function getServer() : Server{
		return $this->server;
	}

	public function isAlive() : bool{
		return $this->alive;
	}

	public function setAlive(bool $isAlive) : void{
		$this->alive = $isAlive;
	}
}
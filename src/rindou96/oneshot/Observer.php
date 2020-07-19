<?php

declare(strict_types=1);

namespace rindou96\oneshot;

use pocketmine\Player;
use pocketmine\Server;

use rindou96\oneshot\Main;

class Observer{

	private $owner;
	private $server;

	public function __construct(Player $player, Main $owner){
		$this->owner = $owner;
		$this->server = $owner->getServer();
		$this->player = $player;
		$this->name = $player->getName();
		$this->kt = 0;
		$this->tasks = [];
	}

	public function getOwner() : Main{
		return $this->owner;
	}

	public function getServer() : Server{
		return $this->server;
	}
}
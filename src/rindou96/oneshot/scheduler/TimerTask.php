<?php

declare(strict_types=1);

namespace rindou96\oneshot\scheduler;

use pocketmine\Server;
use pocketmine\scheduler\Task;

use rindou96\oneshot\Main;
use rindou96\oneshot\manager\GameManager;

class TimerTask extends Task{

	/** @var Main */
	private $owner;
	/** @var Server */
	private $server;

	public function __construct(Main $owner){
		$this->owner = $owner;
		$this->server = $owner->getServer();
	}

	public function onRun(int $tick) : void{
		$game = GameManager::getInstance();
		$time = $game->getTime();
		$game->setTime($time - 1);
		$time = $game->getTime();

		if($time <= 0 || $game->getAliveCount() <= 1){
			$game->endGame();
			return;
		}

		$game->updateScoreboard();
	}

	public function getOwner() : Main{
		return $this->owner;
	}

	public function getServer() : Server{
		return $this->server;
	}
}
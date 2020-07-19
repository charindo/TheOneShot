<?php

declare(strict_types=1);

namespace rindou96\oneshot;

use pocketmine\Server;

use rindou96\oneshot\Main;

class GameManager{

	/** @var GameManager */
	private static $instance;

	/** @var Main */
	private $owner;
	/** @var Server */
	private $server;

	public function __construct(Main $owner){
		self::$instance = $this;

		$this->owner = $owner;
		$this->server = $owner->getServer();

		//TODO
	}

	private function getOwner() : Main{
		return $this->owner;
	}

	private function getServer() : Server{
		return $this->server;
	}

	public static function getInstance() : GameManager{
		return self::$instance;
	}
}
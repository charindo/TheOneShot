<?php

declare(strict_types=1);

namespace rindou96\oneshot;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use rindou96\oneshot\Observer;
use rindou96\oneshot\VersionInfo;
use rindou96\oneshot\commands\GameCommand;
use rindou96\oneshot\entity\Arrow;
use rindou96\oneshot\manager\GameManager;

class Main extends PluginBase{

	/** @var Main */
	public static $plugin;

	/** @var Observer */
	public $observer = [];
	/** @var GameManager */
	public $gameManager;

	public function onEnable(){
		self::$plugin = $this;

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

		if(!file_exists($this->getDataFolder())) mkdir($this->getDataFolder(), 0744, true);
		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"configVersion" => VersionInfo::CONFIG_VERSION,
			"spawnPoint" => [
				"x" => 0,
				"y" => 15,
				"z" => 0,
			],
		]);

		$this->getServer()->getCommandMap()->register("OneShot", new GameCommand("game", $this));

		Entity::registerEntity(Arrow::class, false , ['Arrow']);

		/** GameManager */
		$this->gameManager = new GameManager($this);

		/** Load map */
		$this->getServer()->loadLevel("map");
		
		$this->getLogger()->info("§aTheOneShotを読み込みました");
	}

	public function registerObserver(Player $player) : void{
		$this->unregisterObserver($player->getName());
		$this->observer[$player->getName()] = new Observer($player, $this);
	}

	public function unregisterObserver(string $name) : bool{
		if($this->getObserver($name) !== null){
			unset($this->observer[$name]);
			return true;
		}
		return false;
	}

	public function getObserver(string $name) : ?Observer{
		return $this->observer[$name] ?? null;
	}
}
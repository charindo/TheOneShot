<?php

declare(strict_types=1);

namespace rindou96\oneshot;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use rindou96\oneshot\Observer;
use rindou96\oneshot\commands\GameCommand;
use rindou96\oneshot\entity\Arrow;

class Main extends PluginBase{

	public $observer = [];
	public static $plugin;

	public function onEnable(){
		self::$plugin = $this;

		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

		if(!file_exists($this->getDataFolder())){
			mkdir($this->getDataFolder(), 0744, true);
		}

		$this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
			"configVersion" => VersionInfo::CONFIG_VERSION,
			"defaultTime" => 600,
			"defaultKT" => 20,
		]);
		$this->maps = new Config($this->getDataFolder() . "maps.yml", Config::YAML);

		$this->getServer()->getCommandMap()->register("OneShot", new GameCommand("game", $this));

		Entity::registerEntity(Arrow::class, false , ['Arrow']);
		
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
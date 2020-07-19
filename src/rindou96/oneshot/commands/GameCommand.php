<?php

declare(strict_types=1);

namespace rindou96\oneshot\commands;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\defaults\VanillaCommand;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\lang\TranslationContainer;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\utils\TextFormat;

use rindou96\oneshot\GameManager;
use rindou96\oneshot\Main;

class GameCommand extends VanillaCommand{

	private $owner;
	private $server;

	public function __construct(string $command = "game", Main $owner){
		$description = "システムコマンド";
		parent::__construct($command, $description, $description, [$command]);
		$this->owner = $owner;
		$this->server = $owner->getServer();
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		$name = $sender->getName();

		if($sender->isOp()){
			if(!isset($args[0])){
				$sender->sendMessage("§b》 §fサブコマンドを入力してください");
				return true;
			}elseif($args[0] === "start"){
				//TODO
			}elseif($args[0] === "reset"){
				//TODO
			}elseif($args[0] === "time"){
				if(!isset($args[1])){
					$sender->sendMessage("§b》 §f時間を指定してください");
				}else{
					$this->getOwner()->config->set("defaultTime", (int) $args[1]);
					$sender->sendMessage("§b》 §f時間を設定しました");
				}
			}
		}else $sender->sendMessage("§cこのコマンドを実行する権限がありません");
	}

	public function getOwner() : Main{
		return $this->owner;
	}

	public function getServer() : Server{
		return $this->server;
	}
}
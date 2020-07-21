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
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\utils\TextFormat;

use rindou96\oneshot\Main;
use rindou96\oneshot\manager\GameManager;

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
				GameManager::getInstance()->startGame();
			}elseif($args[0] === "reset"){
				GameManager::getInstance()->resetGame();
				$sender->sendMessage("§b》 §fシステムを初期化しました");
			}elseif($args[0] === "tpa"){
				if($sender instanceof Player){
					if(!isset($args[1])){
						foreach($this->getServer()->getOnlinePlayers() as $player){
							$player->teleport($sender);
						}
						$sender->sendMessage("§b》 一斉TPしました");
					}else{
						$p = $this->getServer()->getPlayer($args[1]);
						if($p !== null){
							$n = $p->getName();
							foreach($this->getServer()->getOnlinePlayers() as $player){
								$player->teleport($p);
							}
							$sender->sendMessage("§b》 §6{$n}§fに一斉TPしました");
						}else $sender->sendMessage("§c》 §fプレイヤーが見つかりませんでした");
					}
				}else $sender->sendMessage("§c》 §fこのコマンドはゲーム内で実行してください");
			}elseif($args[0] === "aiv"){
				if(!isset($args[1])){
					$sender->sendMessage("§b》 §fON / OFFを指定してください");
				}elseif($args[1] === "on" || $args[1] === "true"){
					foreach($this->getServer()->getOnlinePlayers() as $player){
						if($player->getGamemode() !== 1 && $player->getGamemode() !== 3) $player->setInvisible(true);
					}
					$sender->sendMessage("§a》 §f設定が完了しました");
				}elseif($args[1] === "off" || $args[1] === "false"){
					foreach($this->getServer()->getOnlinePlayers() as $player){
						$player->setInvisible(false);
					}
					$sender->sendMessage("§a》 §f設定が完了しました");
				}else $sender->sendMessage("§c》 §f構文に間違いがあります");
			}elseif($args[0] === "spawn"){
				if($sender instanceof Player){
					$this->getOwner()->config->set("spawnPoint", ["x" => $sender->x, "y" => $sender->y, "z" => $sender->z]);
					$this->getOwner()->config->save();
					$sender->sendMessage("§a》 §fスポーン地点を設定しました");
				}else $sender->sendMessage("§c》 §fこのコマンドはゲーム内で実行してください");
			}elseif($args[0] === "time"){
				if(!isset($args[1])){
					$sender->sendMessage("§b》 §f時間を指定してください");
				}else{
					GameManager::getInstance()->setTime((int) $args[1]);
					GameManager::getInstance()->updateScoreboard();
					$sender->sendMessage("§b》 §f時間を設定しました");
				}
			}else $sender->sendMessage("§c》 §fそのようなサブコマンドは存在しません");
		}else $sender->sendMessage("§cこのコマンドを実行する権限がありません");
	}

	public function getOwner() : Main{
		return $this->owner;
	}

	public function getServer() : Server{
		return $this->server;
	}
}
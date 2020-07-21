<?php

declare(strict_types=1);

namespace rindou96\oneshot\manager;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{CompoundTag, StringTag};
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetEntityLinkPacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;

use rindou96\oneshot\Main;
use rindou96\oneshot\scheduler\TimerTask;

class GameManager{//Managerの使い方よくわからない

	/** @var GameManager */
	public static $instance;

	/** @var Main */
	private $owner;
	/** @var Server */
	private $server;

	/** @var int */
	public $status;
	/** @var int */
	public $time;
	/** @var string */
	public $winner;

	/** @var TimerTask */
	public $timerTask = null;

	public function __construct(Main $owner){
		self::$instance = $this;

		$this->owner = $owner;
		$this->server = $owner->getServer();

		$this->status = 0;
		$this->time = 600;
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

	public function getStatus() : int{
		return $this->status;
	}

	public function setStatus(int $status) : void{
		$this->status = $status;
	}

	public function getTime() : int{
		return $this->time;
	}

	public function setTime(int $newTime) : void{
		$this->time = $newTime;
	}

	public function getAlivePlayers() : array{
		$players = [];
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($this->getOwner()->getObserver($player->getName()) !== null && $this->getOwner()->getObserver($player->getName())->isAlive()){
				$players[] = $player;
			}
		}
		return $players;
	}

	public function getAliveCount() : int{
		return count($this->getAlivePlayers());
	}

	public function updateScoreboard() : void{
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$name = $player->getName();

			$pk = new RemoveObjectivePacket;
			$pk->objectiveName = "oneshot";
			$player->sendDataPacket($pk);

			if($this->getStatus() === 1){
				$pk = new SetDisplayObjectivePacket();
				$pk->displaySlot = "sidebar";
				$pk->objectiveName = "oneshot";
				$pk->displayName = "{$name}";
				$pk->criteriaName = "dummy";
				$pk->sortOrder = 0;
				$player->sendDataPacket($pk);

				$pk = new SetScorePacket();
				$pk->type = SetScorePacket::TYPE_CHANGE;

				$id = -1;
				$score = 0;

				$entry = new ScorePacketEntry();
				$entry->objectiveName = "oneshot";
				$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
				$entry->scoreboardId = ++$id;
				$entry->score = ++$score;
				$entry->customName = "§a残り時間: {$this->getTime()}秒 ";
				$pk->entries[] = $entry;

				$entry = new ScorePacketEntry();
				$entry->objectiveName = "oneshot";
				$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
				$entry->scoreboardId = ++$id;
				$entry->score = ++$score;
				$entry->customName = "§6残り人数: {$this->getAliveCount()}人 ";
				$pk->entries[] = $entry;

				$player->sendDataPacket($pk);
			}elseif($this->getStatus() === 2){
				$pk = new SetDisplayObjectivePacket();
				$pk->displaySlot = "sidebar";
				$pk->objectiveName = "oneshot";
				$pk->displayName = "{$name}";
				$pk->criteriaName = "dummy";
				$pk->sortOrder = 0;
				$player->sendDataPacket($pk);

				$pk = new SetScorePacket();
				$pk->type = SetScorePacket::TYPE_CHANGE;

				$id = -1;
				$score = 0;

				$entry = new ScorePacketEntry();
				$entry->objectiveName = "oneshot";
				$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
				$entry->scoreboardId = ++$id;
				$entry->score = ++$score;
				$entry->customName = "§c残り時間: {$this->getTime()}秒 ";
				$pk->entries[] = $entry;

				$entry = new ScorePacketEntry();
				$entry->objectiveName = "oneshot";
				$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
				$entry->scoreboardId = ++$id;
				$entry->score = ++$score;
				$entry->customName = "§6残り人数: {$this->getAliveCount()}人 ";
				$pk->entries[] = $entry;

				if(isset($this->winner)){
					$entry = new ScorePacketEntry();
					$entry->objectiveName = "oneshot";
					$entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
					$entry->scoreboardId = ++$id;
					$entry->score = ++$score;
					$entry->customName = "§e勝者: {$this->winner->getName()} ";
					$pk->entries[] = $entry;
				}

				$player->sendDataPacket($pk);
			}
		}
	}

	public function startGame() : void{
		if($this->timerTask !== null) $this->getOwner()->getScheduler()->cancelTask($this->timerTask->getTaskId());
		$players = [];
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->getGamemode() !== 1 && $player->getGamemode() !== 3) $players[] = $player;
		}

		$this->timerTask = $this->getOwner()->getScheduler()->scheduleRepeatingTask(new TimerTask($this->getOwner()), 20);

		$bow = Item::get(261,0,1);
		$bow->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(22),4545));
		$tag = $bow->getNamedTag() ?? new CompoundTag("",[]);
		$tag->setTag(new StringTag("dontDrop", "dontDrop"), true);

		$arrow = Item::get(262,0,1);
		$tag = $bow->getNamedTag() ?? new CompoundTag("",[]);
		$tag->setTag(new StringTag("dontDrop", "dontDrop"), true);

		foreach($players as $player){
			$player->getInventory()->setItem(0, $bow);
			$player->getInventory()->setItem(9, $arrow);
			$observer = $this->getOwner()->getObserver($player->getName());
			if($observer !== null) $observer->setAlive(true);
			$player->removeAllEffects();
			$player->addEffect(new EffectInstance(Effect::getEffect(1), 2147483647, 2, false)); //SpeedEffect
			$player->addEffect(new EffectInstance(Effect::getEffect(8), 2147483647, 1, false)); //LeapEffect
			$player->setNameTagAlwaysVisible(false);
		}
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->playSound($player, "mob.wither.spawn", 1, 1, $player->x, $player->y, $player->z);
			$player->setInvisible(false);
		}
		$this->getServer()->broadcastMessage("§b§l》 §aゲーム開始");
		$this->setStatus(1);
	}

	public function endGame() : void{
		if($this->timerTask !== null) $this->getOwner()->getScheduler()->cancelTask($this->timerTask->getTaskId());
		if($this->getAliveCount() === 1){
			$this->winner = $this->getAlivePlayers()[0];
			$this->getServer()->broadcastMessage("§b§l》 §fゲームが終了しました");
			$this->getServer()->broadcastMessage("§b§l》 §e勝者: §f{$this->winner->getName()}");
		}else{
			$this->getServer()->broadcastMessage("§b§l》 §fゲームが終了しました");
			$this->getServer()->broadcastMessage("§b§l》 §e引き分け");
		}
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$this->playSound($player, "random.levelup", 1, 0.5, $player->x, $player->y, $player->z);
		}
		$this->setStatus(2);
		$this->updateScoreboard();
	}

	public function resetGame() : void{
		if($this->timerTask !== null) $this->getOwner()->getScheduler()->cancelTask($this->timerTask->getTaskId());
		$this->setStatus(0);
		$this->updateScoreboard();
		$this->getServer()->unloadLevel($this->getServer()->getLevelByName("map"));
		$this->getServer()->loadLevel("map");
		$new_level = $this->getServer()->getLevelByName("map");
		foreach($this->getServer()->getOnlinePlayers() as $player){
			$player->getInventory()->clearAll();
			$player->setGamemode(2);
			$player->teleport(new Position($this->getOwner()->config->get("spawnPoint")["x"], $this->getOwner()->config->get("spawnPoint")["y"], $this->getOwner()->config->get("spawnPoint")["z"], $new_level));
			$player->removeAllEffects();
			$player->getInventory()->clearAll();
			$player->getEnderChestInventory()->clearAll();
			$player->getArmorInventory()->clearAll();
			$player->setNameTagAlwaysVisible(true);
		}
		$this->getOwner()->gameManager = new GameManager($this->getOwner());
	}

	public function playSound(Player $player, string $sound, float $volume, float $pitch, float $x, float $y, float $z) : void{
		$pk = new PlaySoundPacket();
		$pk->soundName = $sound;
		$pk->x = (int) $x;
		$pk->y = (int) $y;
		$pk->z = (int) $z;
		$pk->volume = $volume;
		$pk->pitch = $pitch;
		$player->sendDataPacket($pk);
	}
}
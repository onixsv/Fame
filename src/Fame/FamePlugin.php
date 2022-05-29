<?php
declare(strict_types=1);

namespace Fame;

use OnixUtils\OnixUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use function array_slice;
use function arsort;
use function ceil;
use function count;
use function strtolower;

class FamePlugin extends PluginBase implements Listener{

	/** @var Config */
	protected Config $config;

	protected array $db = [];

	protected function onEnable() : void{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);

		$this->config = new Config($this->getDataFolder() . "Config.yml", Config::YAML, [
			"fame" => []
		]);
		$this->db = $this->config->getAll();
	}

	protected function onDisable() : void{
		$this->config->setAll($this->db);
		$this->config->save();
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($args[0] ?? "x"){
			case "올리기":
			case "up":
				if(isset($this->db["fame"][strtolower($sender->getName())]["time"])){
					if(time() >= $this->getNextDay($sender->getName())){
						if(trim($args[1] ?? "") !== ""){
							if($this->hasData($args[1])){
								if(strtolower($args[1]) !== strtolower($sender->getName())){
									$this->addFame($args[1]);
									OnixUtils::message($sender, "§d" . $args[1] . "§f님의 인기도를 올렸습니다.");
									$this->db["fame"][strtolower($sender->getName())]["time"] = time();
									if(($target = $this->getServer()->getPlayerExact($args[1])) instanceof Player){
										OnixUtils::message($target, "§d" . $sender->getName() . "§f님이 당신의 인기도를 올렸습니다.");
									}
								}else{
									OnixUtils::message($sender, "자기 자신의 인기도를 올릴 수 없습니다.");
								}
							}else{
								OnixUtils::message($sender, "해당 유저는 서버에 접속한 적이 없습니다.");
							}
						}else{
							OnixUtils::message($sender, "인기도를 올릴 유저의 닉네임을 입력해주세요.");
						}
					}else{
						OnixUtils::message($sender, "인기도를 올린뒤 하루 후에 인기도를 올릴 수 있습니다.");
					}
				}else{
					if(trim($args[1] ?? "") !== ""){
						if($this->hasData($args[1])){
							if(strtolower($args[1]) !== strtolower($sender->getName())){
								$this->addFame($args[1]);
								OnixUtils::message($sender, "§d" . $args[1] . "§f님의 인기도를 올렸습니다.");
								$this->db["fame"][strtolower($sender->getName())]["time"] = time();
								if(($target = $this->getServer()->getPlayerExact($args[1])) instanceof Player){
									OnixUtils::message($target, "§d" . $sender->getName() . "§f님이 당신의 인기도를 올렸습니다.");
								}
							}else{
								OnixUtils::message($sender, "자기 자신의 인기도를 올릴 수 없습니다.");
							}
						}else{
							OnixUtils::message($sender, "해당 유저는 서버에 접속한 적이 없습니다.");
						}
					}else{
						OnixUtils::message($sender, "/인기도 올리기 [이름] - 인기도를 올립니다.");
					}
				}
				break;
			case "순위":
			case "rank":
				if(isset($args[1]) && is_numeric($args[1]) && (int) $args[1] > 0){
					$page = (int) $args[1];
				}else{
					$page = 1;
				}
				$arr = [];
				foreach($this->db["fame"] as $playerName => $data){
					$arr[$playerName] = $data["fame"];
				}
				arsort($arr);
				$max = ceil(count($arr) / 5);
				if($page > $max)
					$page = $max;
				$slice = array_slice($arr, (int) (($page - 1) * 5), 5);
				$i = 0;
				foreach($slice as $name => $fame){
					$i++;
					$rank = ($page - 1) * 5 + $i;
					$sender->sendMessage("§d<§f{$rank}위§d> §d{$name}§f: {$fame}");
				}
				break;
			default:
				OnixUtils::message($sender, "/인기도 올리기 [이름] - 인기도를 올립니다.");
				OnixUtils::message($sender, "/인기도 순위 - 인기도 순위를 봅니다.");
		}
		return true;
	}

	public function hasData(string $playerName) : bool{
		return isset($this->db["fame"][strtolower($playerName)]);
	}

	public function addFame(string $playerName) : void{
		if(!isset($this->db["fame"][strtolower($playerName)])){
			$this->db["fame"][strtolower($playerName)] = 0;
		}

		$this->db["fame"][strtolower($playerName)]["fame"] += 1;
	}

	public function getNextDay(string $playerName) : int{
		$h = $this->db["fame"][strtolower($playerName)]["time"] ?? 0;

		return $h + (60 * 60 * 24);
	}

	public function handlePlayerJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();

		if(!$this->hasData($player->getName())){
			$this->db["fame"][strtolower($player->getName())]["fame"] = 0;
		}
	}
}
<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\LobbyControl;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\Gui\Group;
use ManiaLivePlugins\MatchMakingLobby\Windows;
use ManiaLive\Gui\Windows\Shortkey;
use DedicatedApi\Structures;
use ManiaLivePlugins\MatchMakingLobby\Services;
use ManiaLivePlugins\MatchMakingLobby\Config;
use ManiaLivePlugins\MatchMakingLobby\GUI;

class LobbyControl extends \ManiaLive\PluginHandler\Plugin
{

	const PREFIX = 'LobbyInfo$000»$8f0 ';

	/** @var int */
	protected $tick;

	/** @var int */
	protected $mapTick;

	/** @var Config */
	protected $config;

	/** @var MatchMakers\AbstractMatchMaker */
	protected $matchMaker;

	/** @var GUI\AbstractGUI */
	protected $gui;

	/** @var string */
	protected $backLink;

	/** @var int[string] */
	protected $countDown = array();

	/** @var int[string] */
	protected $blockedPlayers = array();

	/** @var Helpers\PenaltiesCalculator */
	protected $penaltiesCalculator;

	/** @var Services\MatchService */
	protected $matchService;

	function onInit()
	{
		$this->setVersion('0.3');
		//Load MatchMaker and helpers for GUI
		$this->config = Config::getInstance();
		$script = $this->storage->gameInfos->scriptName;
		$scriptName = preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script['CurrentValue']);

		$matchMakerClassName = $this->config->matchMakerClassName ? : __NAMESPACE__.'\MatchMakers\\'.$scriptName;
		$guiClassName = $this->config->guiClassName ? : '\ManiaLivePlugins\MatchMakingLobby\GUI\\'.$scriptName;
		$penaltiesCalculatorClassName = $this->config->penaltiesCalculatorClassName ? : __NAMESPACE__.'\Helpers\PenaltiesCalculator';

		$this->setGui(new $guiClassName());
		$this->gui->lobbyBoxPosY = 45;
		$this->setMatchMaker($matchMakerClassName::getInstance());
		$this->setPenaltiesCalculator(new $penaltiesCalculatorClassName);
	}

	function onLoad()
	{
		//Check if Lobby is not running with the match plugin
		if($this->isPluginLoaded('MatchMakingLobby/MatchControl'))
		{
			throw new Exception('Lobby and match cannot be one the same server.');
		}
		$this->enableDatabase();
		$this->createTables();
		$this->enableDedicatedEvents(
			ServerEvent::ON_PLAYER_CONNECT |
			ServerEvent::ON_PLAYER_DISCONNECT |
			ServerEvent::ON_PLAYER_ALLIES_CHANGED |
			ServerEvent::ON_BEGIN_MAP |
			ServerEvent::ON_PLAYER_INFO_CHANGED
		);
		$this->enableTickerEvent();

		$this->matchService = new Services\MatchService($this->connection->getSystemInfo()->titleId, $this->config->script);

		$this->backLink = $this->storage->serverLogin.':'.$this->storage->server->password.'@'.$this->connection->getSystemInfo()->titleId;

		$this->setLobbyInfo();
		foreach(array_merge($this->storage->players, $this->storage->spectators) as $login => $obj)
		{
			$this->gui->createPlayerList($login, $this->blockedPlayers);
		}

		foreach($this->storage->players as $login => $player)
		{
			$this->onPlayerNotReady($login);
		}
		foreach($this->storage->spectators as $login => $player)
		{
			$this->onPlayerNotReady($login);
		}

		$this->registerLobby();

		$playersCount = $this->getReadyPlayersCount();
		$totalPlayerCount = $this->getTotalPlayerCount();

		$lobbyWindow = Windows\LobbyWindow::Create();
		$lobbyWindow->setAlign('right', 'bottom');
		$lobbyWindow->setPosition(170, 45);
		$lobbyWindow->set($this->storage->server->name, $playersCount, $totalPlayerCount, $this->getPlayingPlayersCount());
		$lobbyWindow->show();

		$feedback = Windows\Feedback::Create();
		$feedback->setAlign('right', 'bottom');
		$feedback->setPosition(160.1, 75);
		$feedback->show();
	}

	function onUnload()
	{
		$this->setLobbyInfo(false);
		parent::onUnload();
	}

	function onPlayerConnect($login, $isSpectator)
	{
		$message = '';
		$player = Services\PlayerInfo::Get($login);
		$message = ($player->ladderPoints ? $this->gui->getPlayerBackLabelPrefix() : '').$this->gui->getNotReadyText();
		$player->setAway(false);
		$player->setMatch();
		$player->ladderPoints = $this->matchMaker->getPlayerScore($login);
		$player->allies = $this->storage->getPlayerObject($login)->allies;

		$this->gui->createLabel($login, $message);
		$this->onSetShortKey($login, false);

		$this->gui->updatePlayerList($login, $this->blockedPlayers);
		$this->gui->createPlayerList($login, $this->blockedPlayers);

		$this->updateLobbyWindow();
		$leaves = $this->getLeavesCount($login);
		$this->checkKarma($login, $leaves);
	}

	function onPlayerDisconnect($login)
	{
		$player = Services\PlayerInfo::Get($login);
		$player->setAway();

		list($server, ) = $player->getMatch();
		$groupName = 'match-'.$server;
		$group = Group::Get($groupName);

		if($group && $group->contains($login) && $this->countDown[$groupName] > 0)
		{
			$this->onPlayerNotReady($login);
		}

		$this->gui->removePlayerFromPlayerList($login);

		$this->updateLobbyWindow();
	}

	function onPlayerInfoChanged($playerInfo)
	{
		$playerInfo = Structures\Player::fromArray($playerInfo);
		if($playerInfo->hasJoinedGame)
		{
			if($this->matchService->isInMatch($playerInfo->login))
			{
				//TODO Change The Label
				list($server, ) = Services\PlayerInfo::Get($playerInfo->login)->getMatch();
				$jumper = Windows\ForceManialink::Create($playerInfo->login);
				$jumper->set('maniaplanet://#qjoin='.$server.'@'.$this->connection->getSystemInfo()->titleId);
				$jumper->show();

				$this->gui->createLabel($playerInfo->login, $this->gui->getMatchInProgressText());
				return;
			}

			//TODO Something for new players to set them ready ?
			//TODO Splashscreen ??
		}
	}

	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->mapTick = 0;
	}

	//Core of the plugin
	function onTick()
	{
		foreach(array_merge($this->storage->players, $this->storage->spectators) as $player)
		{
			$leaves = $this->getLeavesCount($player->login);
			$this->checkKarma($player->login, $leaves);
		}

		foreach($this->blockedPlayers as $login => $countDown)
		{
			$this->blockedPlayers[$login] = --$countDown;
			if($this->blockedPlayers[$login] == 0)
			{
				unset($this->blockedPlayers[$login]);
				$this->onPlayerNotReady($login);
			}
		}

		$matches = $this->matchMaker->run(array_keys($this->blockedPlayers));
		foreach($matches as $match)
		{
			if(!($server = $this->matchService->getServer())) break;
			$this->prepareMatch($server, $match);
		}

		foreach($this->countDown as $groupName => $countDown)
		{
			switch(--$countDown)
			{
				case -1:
					Windows\ForceManialink::Erase(Group::Get($groupName));
					Group::Erase($groupName);
					unset($this->countDown[$groupName]);
					break;
				case 0:
					$group = Group::Get($groupName);
					$players = array_map(array($this->storage, 'getPlayerObject'), $group->toArray());

					$nicknames = array();
					foreach($players as $player)
					{
						if($player) $nicknames[] = '$<'.$player->nickName.'$>';
					}

					Windows\ForceManialink::Create($group)->show();
					$this->connection->chatSendServerMessage(self::PREFIX.implode(' & ', $nicknames).' join their match server.', null);
				default:
					$this->countDown[$groupName] = $countDown;
			}
		}

		if(++$this->mapTick % 1800 == 0) $this->connection->nextMap();
		if(++$this->tick % 30 == 0) array_map(array($this, 'cleanPlayerStillMatch'), Services\PlayerInfo::GetReady());

		$this->setLobbyInfo();
		$this->updateLobbyWindow();
		$this->registerLobby();
		Services\PlayerInfo::CleanUp();
	}

	function onPlayerReady($login)
	{
		$player = Services\PlayerInfo::Get($login);
		$player->setReady(true);
		$this->onSetShortKey($login, true);
		$this->gui->createLabel($login, $this->gui->getReadyText());

		$this->gui->updatePlayerList($login, $this->blockedPlayers);

		$this->setLobbyInfo();
		$this->updateLobbyWindow();
	}

	function onPlayerNotReady($login)
	{
		$player = Services\PlayerInfo::Get($login);
		$player->setReady(false);
		$this->onSetShortKey($login, false);
		if($this->matchService->isInMatch($login))
		{
			$this->cancelMatch($login);
		}
		$this->gui->createLabel($login, $this->gui->getNotReadyText());

		$this->gui->updatePlayerList($login, $this->blockedPlayers);

		$this->setLobbyInfo();
		$this->updateLobbyWindow();
	}

	function onPlayerAlliesChanged($login)
	{
		$player = $this->storage->getPlayerObject($login);
		if($player)
		{
			Services\PlayerInfo::Get($login)->allies = $player->allies;
			$this->gui->updatePlayerList($login, $this->blockedPlayers);
			foreach($player->allies as $ally)
				$this->gui->updatePlayerList($ally, $this->blockedPlayers);
		}
	}

	private function cancelMatch($login)
	{
		list($server, $match) = Services\PlayerInfo::Get($login)->getMatch();
		$groupName = 'match-'.$server;
		Windows\ForceManialink::Erase(Group::Get($groupName));
		Group::Erase($groupName);
		unset($this->countDown[$groupName]);
		$this->matchService->removeMatch($server);
		$quitterService = new Services\QuitterService($this->storage->serverLogin);
		return $quitterService->register($login);

		foreach($match->players as $playerLogin)
		{
			if($playerLogin != $login)
			{
				$this->onPlayerReady($playerLogin);
			}
			Services\PlayerInfo::Get($playerLogin)->setMatch();
			$this->gui->updatePlayerList($playerLogin, $this->blockedPlayers);
		}
	}

	private function prepareMatch($server, $match)
	{
		$groupName = 'match-'.$server;
		$this->matchService->registerMatch($this->storage->serverLogin, $server, $match);

		Group::Erase($groupName);
		$group = Group::Create('match-'.$server, $match->players);
		$jumper = Windows\ForceManialink::Create($group);
		$jumper->set('maniaplanet://#qjoin='.$server.'@'.$this->connection->getSystemInfo()->titleId);
		$this->countDown[$groupName] = 11;

		foreach($match->players as $player)
		{
			Services\PlayerInfo::Get($player)->setMatch($server, $match);
			$this->gui->createLabel($player, $this->gui->getLaunchMatchText($match, $player), $this->countDown[$groupName] - 1);
			$this->gui->updatePlayerList($player, $this->blockedPlayers);
		}
	}

	private function getReadyPlayersCount()
	{
		$count = 0;
		foreach($this->storage->players as $player)
			$count += Services\PlayerInfo::Get($player->login)->isReady() ? 1 : 0;
		foreach($this->storage->spectators as $player)
			$count += Services\PlayerInfo::Get($player->login)->isReady() ? 1 : 0;

		return $count;
	}

	private function getTotalPlayerCount()
	{
		//Number of matchs in DB minus matchs prepared
		//Because player are still on the server
		$playingPlayers = $this->getPlayingPlayersCount();

		$playerCount = count($this->storage->players) + count($this->storage->spectators);

		return $playerCount + $playingPlayers;
	}

	private function getPlayingPlayersCount()
	{
		$matchCount = $this->matchService->getCount($this->storage->serverLogin);

		return ($matchCount - count($this->countDown)) * $this->matchMaker->playerPerMatch;
	}

	private function getTotalSlots()
	{
		$lobbyService = new Services\LobbyService($this->connection->getSystemInfo()->titleId,
			$this->config->script);
		return $lobbyService->getServersCount($this->storage->serverLogin) * $this->matchMaker->playerPerMatch + $this->storage->server->currentMaxPlayers;
	}

	private function registerLobby()
	{
		$lobbyService = new Services\LobbyService($this->connection->getSystemInfo()->titleId,
			$this->config->script);
		$connectedPlayerCount = count($this->storage->players) + count($this->storage->spectators);
		$lobbyService->register($this->storage->serverLogin, $this->getReadyPlayersCount(), $connectedPlayerCount,
			$this->getPlayingPlayersCount(), $this->storage->server->name, $this->backLink);
	}

	private function getLeavesCount($login)
	{
		$quitterService = new Services\QuitterService($this->storage->serverLogin);
		return $quitterService->getCount($login);
	}

	/**
	 * @param $login
	 */
	private function checkKarma($login, $leavesCount)
	{

		$karma = $this->penaltiesCalculator->calculateKarma($login, $leavesCount);
		if(Services\PlayerInfo::Get($login)->karma < $karma || array_key_exists($login, $this->blockedPlayers))
		{
			$player = $this->storage->getPlayerObject($login);
			if(!array_key_exists($login, $this->blockedPlayers))
			{
				$penalty = $this->penaltiesCalculator->getPenalty($login, $karma);
				$this->blockedPlayers[$login] = 60 * $penalty;
				$this->connection->chatSendServerMessage(
					sprintf(self::PREFIX.'$<%s$> is suspended for %d minutes for leaving matchs.', $player->nickName, $penalty)
				);
			}

			$this->onPlayerNotReady($login);
			$this->gui->createLabel($login, $this->gui->getBadKarmaText($this->blockedPlayers[$login]));
			$shortKey = Shortkey::Create($login);
			$shortKey->removeCallback($this->gui->actionKey);
			$this->gui->updatePlayerList($login, $this->blockedPlayers);
		}
		Services\PlayerInfo::Get($login)->karma = $karma;
	}

	private function cleanPlayerStillMatch(Services\PlayerInfo $player)
	{
		if(!$this->matchService->isInMatch($player->login))
		{
			$player->setMatch();
		}
	}

	protected function onSetShortKey($login, $ready)
	{
		$shortKey = Shortkey::Create($login);
		$callback = array($this, $ready ? 'onPlayerNotReady' : 'onPlayerReady');
		$shortKey->removeCallback($this->gui->actionKey);
		$shortKey->addCallback($this->gui->actionKey, $callback);
	}

	private function updateLobbyWindow()
	{
		$playersCount = $this->getReadyPlayersCount();
		$totalPlayerCount = $this->getTotalPlayerCount();
		$playingPlayersCount = $this->getPlayingPlayersCount();
		$this->gui->updateLobbyWindow($this->storage->server->name, $playersCount, $totalPlayerCount, $playingPlayersCount);
	}

	private function setLobbyInfo($enable = true)
	{
		if($enable)
		{
			$lobbyPlayers = $this->getTotalPlayerCount();
			$maxPlayers = $this->getTotalSlots();
		}
		else
		{
			$lobbyPlayers = count($this->storage->players);
			$maxPlayers = $this->storage->server->currentMaxPlayers;
		}
		$this->connection->setLobbyInfo($enable, $lobbyPlayers, $maxPlayers);
	}

	protected function setGui(GUI\AbstractGUI $GUI)
	{
		$this->gui = $GUI;
	}

	protected function setMatchMaker(MatchMakers\AbstractMatchMaker $matchMaker)
	{
		$this->matchMaker = $matchMaker;
	}

	protected function setPenaltiesCalculator(Helpers\PenaltiesCalculator $penaltiesCalculator)
	{
		$this->penaltiesCalculator = $penaltiesCalculator;
	}

	private function createTables()
	{
		$this->db->execute(
			<<<EOLobbies
CREATE TABLE IF NOT EXISTS `Lobbies` (
	`login` VARCHAR(25) NOT NULL,
	`readyPlayers` INT(10) NOT NULL,
	`connectedPlayers` INT(10) NOT NULL,
	`playingPlayers` INT(10) NOT NULL,
	`name` VARCHAR(76) NOT NULL,
	`backLink` VARCHAR(76) NOT NULL,
	PRIMARY KEY (`login`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOLobbies
		);
		$this->db->execute(
			<<<EOServers
CREATE TABLE IF NOT EXISTS `Servers` (
  `login` varchar(25) NOT NULL,
  `title` varchar(51) NOT NULL,
  `script` varchar(50) DEFAULT NULL,
  `lastLive` datetime NOT NULL,
  `lobby` varchar(25) DEFAULT NULL COMMENT 'login@title',
  `players` text,
  PRIMARY KEY (`login`),
  KEY `title` (`title`),
  KEY `script` (`script`),
  KEY `lastLive` (`lastLive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOServers
		);

		$this->db->execute(
			<<<EOMatchs
CREATE TABLE IF NOT EXISTS `PlayedMatchs` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`server` VARCHAR(25) NOT NULL,
	`title` varchar(51) NOT NULL,
	`script` VARCHAR(50) NOT NULL,
	`match` TEXT NOT NULL,
	`playedDate` DATETIME NOT NULL,
	PRIMARY KEY (`id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOMatchs
		);

		$this->db->execute(
			<<<EOQuitters
CREATE TABLE IF NOT EXISTS `Quitters` (
	`playerLogin` VARCHAR(25) NOT NULL,
	`creationDate` DATETIME NOT NULL,
	`lobby` VARCHAR(25) NOT NULL
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOQuitters
		);
	}

}

?>
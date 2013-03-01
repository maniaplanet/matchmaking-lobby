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
use ManiaLivePlugins\MatchMakingLobby\Windows\Label;

class LobbyControl extends \ManiaLive\PluginHandler\Plugin
{

	const PREFIX = 'LobbyInfo$000»$8f0 ';
	const PENALTY_TIME = 4;
	
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
	protected $hall;

	/** @var string */
	protected $modeClause;

	/** @var int[string] */
	protected $countDown = array();
	
	/** @var int[string] */
	protected $blockedPlayers = array();
	
	/** @var Helpers\PenaltiesCalculator */
	protected $penaltiesCalculator;
	
	function onInit()
	{
		$this->setVersion('0.2');
		$this->config = Config::getInstance();
		$scriptName = $this->connection->getScriptName();
		$scriptName = end(explode('\\', $scriptName['CurrentValue']));
		$scriptName = str_ireplace('.script.txt', '', $scriptName);

		$matchMakerClassName = $this->config->matchMakerClassName ? : __NAMESPACE__.'\MatchMakers\\'.$scriptName;
		$guiClassName = $this->config->guiClassName ? : __NAMESPACE__.'\GUI\\'.$scriptName;
		$penaltiesCalculatorClassName = $this->config->penaltiesCalculatorClassName ? : __NAMESPACE__.'\Helpers\PenaltiesCalculator';
		
		$this->setGui(new $guiClassName());
		$this->setMatchMaker($matchMakerClassName::getInstance());
		$this->setPenaltiesCalculator(new $penaltiesCalculatorClassName);
	}
	
	function onLoad()
	{
		$this->enableDatabase();
		$this->createTables();
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT | ServerEvent::ON_PLAYER_ALLIES_CHANGED | ServerEvent::ON_BEGIN_MAP);
		$this->enableTickerEvent();

		$this->hall = $this->storage->serverLogin.':'.$this->storage->server->password.'@'.$this->connection->getSystemInfo()->titleId;
		$this->modeClause = sprintf('title=%s', $this->db->quote($this->connection->getSystemInfo()->titleId));
		if(strpos($this->connection->getSystemInfo()->titleId, '@') === false)
				$this->modeClause .= sprintf(' AND script=%s', $this->db->quote($this->config->script));

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
		if($this->isInMatch($login))
		{
			//TODO Change The Label
			list($server, ) = PlayerInfo::Get($login)->getMatch();
			$jumper = Windows\ForceManialink::Create($login);
			$jumper->set('maniaplanet://#qjoin='.$server.'@'.$this->connection->getSystemInfo()->titleId);
			$jumper->show();

			$this->gui->createLabel($login, $this->gui->getMatchInProgressText());
			return;
		}

		$message = '';
		$player = PlayerInfo::Get($login);
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
		$player = PlayerInfo::Get($login);
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
	
	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->mapTick = 0;
	}

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
			if(!($server = $this->getServer())) break;
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
						if($player)
							$nicknames[] = '$<'.$player->nickName.'$>';
					}
					
					Windows\ForceManialink::Create($group)->show();
					$this->connection->chatSendServerMessage(self::PREFIX.implode(' & ', $nicknames).' join their match server.', null);
				default:
					$this->countDown[$groupName] = $countDown;
			}
		}
		
		if(++$this->tick % 3600 == 0)
			$this->cleanKarma();
		if(++$this->mapTick % 1800 == 0)
			$this->connection->nextMap();
		if($this->tick % 30 == 0)
			array_map(array($this,'cleanPlayerStillMatch'), PlayerInfo::GetReady());
		
		$this->setLobbyInfo();
		$this->updateLobbyWindow();
		$this->registerLobby();
		PlayerInfo::CleanUp();
	}

	function onPlayerReady($login)
	{
		$player = PlayerInfo::Get($login);
		$player->setReady(true);
		$this->onSetShortKey($login, true);
		$this->gui->createLabel($login, $this->gui->getReadyText());

		$this->gui->updatePlayerList($login, $this->blockedPlayers);

		$this->setLobbyInfo();
		$this->updateLobbyWindow();
	}

	function onPlayerNotReady($login)
	{
		$player = PlayerInfo::Get($login);
		$player->setReady(false);
		$this->onSetShortKey($login, false);
		if($player->isInMatch())
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
			PlayerInfo::Get($login)->allies = $player->allies;
			$this->gui->updatePlayerList($login, $this->blockedPlayers);
			foreach($player->allies as $ally)
				$this->gui->updatePlayerList($ally, $this->blockedPlayers);
		}
	}

	private function getServer()
	{
		return $this->db->execute(
				'SELECT login FROM Servers '.
				'WHERE '.$this->modeClause.' AND hall IS NULL AND DATE_ADD(lastLive, INTERVAL 20 SECOND) > NOW() '.
				'ORDER BY RAND() LIMIT 1'
			)->fetchSingleValue(null);
	}

	private function isInMatch($login)
	{
		list($server, $players) = PlayerInfo::Get($login)->getMatch();
		return $this->db->execute(
				'SELECT count(*) FROM Servers '.
				'WHERE login = %s and players = %s', $this->db->quote($server), $this->db->quote(json_encode($players))
			)->fetchSingleValue(false);
		;
	}

	private function cancelMatch($login)
	{
		list($server, $match) = PlayerInfo::Get($login)->getMatch();
		$groupName = 'match-'.$server;
		Windows\ForceManialink::Erase(Group::Get($groupName));
		Group::Erase($groupName);
		unset($this->countDown[$groupName]);
		$this->db->execute(
			'UPDATE Servers SET hall=NULL, players=NULL WHERE login=%s', $this->db->quote($server)
		);
		$this->registerCancel($login);

		foreach($match->players as $playerLogin)
		{
			if($playerLogin != $login)
			{
				$this->onPlayerReady($playerLogin);
			}
			PlayerInfo::Get($playerLogin)->setMatch();
			$this->gui->updatePlayerList($playerLogin, $this->blockedPlayers);
		}
	}

	private function prepareMatch($server, $match)
	{
		$groupName = 'match-'.$server;
		$this->db->execute(
			'UPDATE Servers SET hall=%s, players=%s WHERE login=%s', $this->db->quote($this->storage->serverLogin),
			$this->db->quote(json_encode($match)), $this->db->quote($server)
		);

		Group::Erase($groupName);
		$group = Group::Create('match-'.$server, $match->players);
		$jumper = Windows\ForceManialink::Create($group);
		$jumper->set('maniaplanet://#qjoin='.$server.'@'.$this->connection->getSystemInfo()->titleId);
		$this->countDown[$groupName] = 11;

		foreach($match->players as $player)
		{
			PlayerInfo::Get($player)->setMatch($server, $match);
			$this->gui->createLabel($player, $this->gui->getLaunchMatchText($match, $player), $this->countDown[$groupName] - 1);
			$this->gui->updatePlayerList($player, $this->blockedPlayers);
		}
	}

	private function getReadyPlayersCount()
	{
		$count = 0;
		foreach($this->storage->players as $player)
			$count += PlayerInfo::Get($player->login)->isReady() ? 1 : 0;
		foreach($this->storage->spectators as $player)
			$count += PlayerInfo::Get($player->login)->isReady() ? 1 : 0;

		return $count;
	}

	private function getTotalPlayerCount()
	{
		//Number of matchs in DB minus matchs prepared
		//Because player are still on the server
		$playingPlayers = $this->getPlayingPlayersCount();

		$playerCount = count($this->connection->getPlayerList(-1, 0));

		return $playerCount + $playingPlayers;
	}
	
	private function getPlayingPlayersCount()
	{
		$matchCount = $this->db->execute(
				'SELECT COUNT(*) FROM Servers '.
				'WHERE '.$this->modeClause.' AND hall = %s', $this->db->quote($this->storage->serverLogin)
			)->fetchSingleValue(0);
		
		return ($matchCount - count($this->countDown)) * $this->matchMaker->playerPerMatch;
	}

	private function getTotalSlots()
	{
		return $this->db->execute(
				'SELECT COUNT(*) FROM Servers '.
				'WHERE (DATE_ADD(lastLive, INTERVAL 20 SECOND) > NOW() AND %s) OR '.
				'hall = %s', $this->modeClause, $this->db->quote($this->storage->serverLogin)
			)->fetchSingleValue(null) * $this->matchMaker->playerPerMatch + $this->storage->server->currentMaxPlayers;
	}

	private function registerLobby()
	{
		$this->db->execute(
			'INSERT INTO Halls VALUES (%s, %d, %d, %d, %s, %s) '.
			'ON DUPLICATE KEY UPDATE '.
			'readyPlayers = VALUES(readyPlayers), '.
			'connectedPlayers = VALUES(connectedPlayers), '.
			'playingPlayers = VALUES(playingPlayers)',
			$this->db->quote($this->storage->serverLogin), 
			$this->getReadyPlayersCount(),
			count($this->connection->getPlayerList(-1, 0)),
			$this->getPlayingPlayersCount(),
			$this->db->quote($this->storage->server->name),
			$this->db->quote($this->hall)
		);
	}
	
	private function getLeavesCount($login)
	{
		return $this->db->query(
			'SELECT count(*) FROM Quitters '.
			'WHERE playerLogin = %s '.
			'AND hall = %s '.
			'AND DATE_ADD(creationDate, INTERVAL 1 HOUR) > NOW()',
			$this->db->quote($login),
			$this->db->quote($this->storage->serverLogin)
		)->fetchSingleValue();
	}
	
	/**
	 * @param $login
	 */
	private function checkKarma($login, $leavesCount)
	{
		
		$karma = call_user_func($this->config->penaltyClass.'::calculateKama', $login, $leavesCount);
		if(PlayerInfo::Get($login)->karma < $karma || array_key_exists($login, $this->blockedPlayers))
		{
			$player = $this->storage->getPlayerObject($login);
			if(!array_key_exists($login, $this->blockedPlayers))
			{
				$penalty = call_user_func($this->config->penaltyClass.'::getPenalty', $login, $karma);
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
		PlayerInfo::Get($login)->karma = $karma;
	}
	
	private function cleanKarma()
	{
		$this->db->execute('DELETE FROM Quitters WHERE DATE_ADD(creationDate, INTERVAL 1 HOUR) < NOW()');
	}
	
	private function registerCancel($login)
	{
		$this->db->execute(
			'INSERT INTO Quitters VALUES (%s,NOW(), %s)', 
			$this->db->quote($login),
			$this->db->quote($this->storage->serverLogin)
		);
	}
	
	private function cleanPlayerStillMatch(PlayerInfo $player)
	{
		if(!$this->isPlayerMatchExist($player->login))
		{
			$player->setMatch();
		}
	}
	
	private function isPlayerMatchExist($login)
	{
		list($server, $match) = PlayerInfo::Get($login)->getMatch();
		
		return $this->db->query('SELECT IF(count(*), TRUE, FALSE) FROM Servers WHERE hall = %s and `players` = %s',
			$this->db->quote($server),
			$this->db->quote(json_encode($match))
		)->fetchSingleValue();
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
			$lobbyPlayers = count($this->connection->getPlayerList(-1, 0));
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
		$this->penaltyCalculator = $penaltiesCalculator;
	}
	
	private function createTables()
	{
		$this->db->execute(
			<<<EOHalls
CREATE TABLE IF NOT EXISTS `Halls` (
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
EOHalls
		);
		$this->db->execute(
			<<<EOServers
CREATE TABLE IF NOT EXISTS `Servers` (
  `login` varchar(25) NOT NULL,
  `title` varchar(51) NOT NULL,
  `script` varchar(50) DEFAULT NULL,
  `lastLive` datetime NOT NULL,
  `hall` varchar(25) DEFAULT NULL COMMENT 'login@title',
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
	`hall` VARCHAR(25) NOT NULL
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOQuitters
		);
	}

}

?>
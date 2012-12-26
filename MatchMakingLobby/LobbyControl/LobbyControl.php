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
	const PREFIX = 'Lobby$08fBot$000»$8f0 ';
	
	/** @var Config */
	private $config;
	/** @var MatchMaker */
	private $matchMaker;
	
	/** @var int */
	private $tick = 0;
	/** @var string */
	private $hall;
	/** @var string */
	private $modeClause;

	function onInit()
	{
		$this->setVersion('1.0');
		$this->config = Config::getInstance();
		$this->matchMaker = MatchMaker::getInstance();
	}
	
	function onLoad()
	{
		$this->enableDatabase();
		$this->createTables();
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT);
		$this->enableTickerEvent();
		
		$scriptSettings = $this->connection->getModeScriptSettings();
		$scriptSettings['S_UseLobby'] = true;
		$this->connection->setModeScriptSettings($scriptSettings);
		
		$ah = \ManiaLive\Gui\ActionHandler::getInstance();
		$this->hall = $this->storage->serverLogin.':'.$this->storage->server->password.'@'.$this->connection->getSystemInfo()->titleId;
		$this->modeClause = sprintf('title=%s', $this->db->quote($this->connection->getSystemInfo()->titleId));
		if(strpos($this->connection->getSystemInfo()->titleId, '@') === false)
			$this->modeClause .= sprintf(' AND script=%s', $this->db->quote($this->config->script));
		
		$playerList = Windows\PlayerList::Create();
		$playerList->setAlign('right');
		$playerList->setPosition(170, 48);
		$playerList->show();
		
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
		$lobbyWindow->set($this->storage->server->name, $playersCount, $totalPlayerCount);
		$lobbyWindow->show();
	}
	
	function onPlayerConnect($login, $isSpectator)
	{
		if($this->isInMatch($login))
		{
			//TODO Change The Label
			list($server, $players) = PlayerInfo::Get($login)->getMatch();
			$jumper = Windows\ForceManialink::Create($login);
			$jumper->set('maniaplanet://#qjoin='.$server.'@'.$this->connection->getSystemInfo()->titleId);
			$jumper->show();
			
			$this->createLabel($login, 'You have a match in progress. Prepare to be transfered.');
			return;
		}
		
		$message = '';
		$playerObject = $this->storage->getPlayerObject($login);
		$player = PlayerInfo::Get($login);
		$message = ($player->ladderPoints ? 'Welcome back. ' : '').'Press F6 to find a match.';
		$player->setAway(false);
		$player->setMatch();
		$player->ladderPoints = $this->getPlayerScore($login);
		$this->newPlayers[] = $login;
		
		$this->createLabel($login, $message);
		$this->onSetShortKey($login, false);
		
		$playerList = Windows\PlayerList::Create();
		$playerList->addPlayer($login);
		$playerList->redraw();
		
		$this->updateLobbyWindow();
	}
	
	function onPlayerDisconnect($login)
	{
		PlayerInfo::Get($login)->setAway();
		$playerList = Windows\PlayerList::Create();
		$playerList->removePlayer($login);
		$playerList->redraw();
		
		$this->updateLobbyWindow();
	}
	
	function onTick()
	{
		switch(++$this->tick % 15)
		{
			case 0:
				$matches = $this->matchMaker->run($this->config->playersNeeded);
				foreach($matches as $players)
				{
					if(!($server = $this->getServer())) break;
					$this->prepareMatch($server, $players);
				}
				$this->updateLobbyWindow();
				$this->registerLobby();
				break;
			case 5:
				PlayerInfo::CleanUp();
				break;
			case 10:
				foreach(Windows\ForceManialink::GetAll() as $jumper)
					$jumper->show();
				break;
			case 11:
				Windows\ForceManialink::EraseAll();
		}
	}
	
	function onPlayerReady($login)
	{
		PlayerInfo::Get($login)->setReady(true);
		$this->onSetShortKey($login, true);
		$this->createLabel($login, 'Searching for an opponent, F6 to cancel.');
		
		$playerList = Windows\PlayerList::Create();
		$playerList->setPlayer($login, true);
		$playerList->redraw();
		
		$this->updateLobbyWindow();
	}
	
	function onPlayerNotReady($login)
	{
		PlayerInfo::Get($login)->setReady(false);
		$this->onSetShortKey($login, false);
		if($this->isInMatch($login))
		{
			$this->cancelMatch($login);
		}
		$this->createLabel($login, 'Press F6 to find a match.');

		$playerList = Windows\PlayerList::Create();
		$playerList->setPlayer($login, false);
		$playerList->redraw();
		
		$this->updateLobbyWindow();
	}
	
	private function getPlayerScore($login)
	{
		//Change here if new way to get Ladder points
		return $this->storage->getPlayerObject($login)->ladderStats['PlayerRankings'][0]['Score'];
	}


	protected function onSetShortKey($login, $ready)
	{
		$shortKey = Shortkey::Create($login);
		$callback = array($this, $ready ? 'onPlayerNotReady' : 'onPlayerReady');
		$shortKey->removeCallback(Shortkey::F6);
		$shortKey->addCallback(Shortkey::F6, $callback);
	}
	
	private function createLabel($login, $message, $countdown = null)
	{
		Label::Erase($login);
		$confirm = Label::Create($login);
		$confirm->setPosition(0, 40);
		$confirm->setMessage($message, $countdown);
		$confirm->show();
	}
	
	private function updateLobbyWindow()
	{
		$playersCount = $this->getReadyPlayersCount();
		$totalPlayerCount = $this->getTotalPlayerCount();
		$lobbyWindow = Windows\LobbyWindow::Create();
		$lobbyWindow->set($this->storage->server->name, $playersCount, $totalPlayerCount);
		$lobbyWindow->show();
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
			'WHERE login = %s and players = %s', 
			$this->db->quote($server), 
			$this->db->quote(json_encode($players))
		)->fetchSingleValue(false);;
	}
	
	private function cancelMatch($login)
	{
		list($server, $players) = PlayerInfo::Get($login)->getMatch();
		Group::Erase('match-'.$server);
		$this->db->execute(
			'UPDATE Servers SET hall=NULL, players=NULL WHERE login=%s', $this->db->quote($server)
		);
		
		foreach($players as $playerLogin)
		{
			if($playerLogin != $login)
			{
				$this->onPlayerReady($playerLogin);
			}
		}
	}
	
	private function prepareMatch($server, $players)
	{
		$this->db->execute(
				'UPDATE Servers SET hall=%s, players=%s WHERE login=%s',
				$this->db->quote($this->storage->serverLogin),
				$this->db->quote(json_encode($players)),
				$this->db->quote($server)
			);
		
		Group::Erase('match-'.$server);
		$group = Group::Create('match-'.$server, $players);
		$jumper = Windows\ForceManialink::Create($group);
		$jumper->set('maniaplanet://#qjoin='.$server.'@'.$this->connection->getSystemInfo()->titleId);
		
		foreach($players as $key => $player)
		{
			PlayerInfo::Get($player)->setMatch($server, $players);
			$opponent = $this->storage->getPlayerObject($players[($key + 1) % 2])->nickName;
			$this->createLabel($player,
				sprintf('$0F0Match against $<%s$> starts in $<$FFF%%2d$>, F6 to cancel...', $opponent), 10);
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
		$matchCount = $this->db->execute(
				'SELECT COUNT(*) FROM Servers '.
				'WHERE '.$this->modeClause.' AND hall = %s',
				$this->db->quote($this->storage->serverLogin)
			)->fetchSingleValue(null);
		
		$playerCount = count($this->connection->getPlayerList(-1, 0));
		
		return $playerCount + $matchCount * 2;
	}
	
	private function registerLobby()
	{
		$this->db->execute(
			'INSERT INTO Halls VALUES (%s, %d, %d, %s, %s) '.
			'ON DUPLICATE KEY UPDATE readyPlayers = VALUES(readyPlayers), connectedPlayers = VALUES(connectedPlayers)',
			$this->db->quote($this->storage->serverLogin), $this->getReadyPlayersCount(),
			count($this->connection->getPlayerList(-1, 0)),
			$this->db->quote($this->storage->server->name), $this->db->quote($this->hall)
		);
	}
	
	private function createTables()
	{
		$this->db->execute(
			<<<EOHalls
CREATE TABLE IF NOT EXISTS `Halls` (
	`login` VARCHAR(25) NOT NULL,
	`readyPlayers` INT(10) NOT NULL,
	`connectedPlayers` INT(10) NOT NULL,
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
	}
}

?>
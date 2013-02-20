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

	/** @var MatchMakers\AbstractMatchMaker */
	private $matchMaker;

	/** @var GUI\AbstractGUI */
	private $gui;

	/** @var int */
	private $tick = 0;

	/** @var string */
	private $hall;

	/** @var string */
	private $modeClause;

	/** @var int[] */
	private $countDown = array();
	
	/** @var array */
	private $newPlayers = array();

	function onInit()
	{
		$this->setVersion('1.0');
		$this->config = Config::getInstance();
		$scriptInfo = $this->connection->getModeScriptInfo();
		$scriptName = ($this->config->script ? : end(explode('\\', $scriptInfo->name)));

		$matchMakerClassName = '\ManiaLivePlugins\MatchMakingLobby\LobbyControl\MatchMakers\\'.$scriptName;
		$guiClassName = '\ManiaLivePlugins\MatchMakingLobby\LobbyControl\GUI\\'.$scriptName;
		if(!class_exists($matchMakerClassName))
		{
			throw new \UnexpectedValueException($scriptName.' has no matchMaker class');
		}
		$this->matchMaker = $matchMakerClassName::getInstance();

		if(!class_exists($guiClassName))
		{
			throw new \UnexpectedValueException($guiClassName.' has no GUI class');
		}
		$this->gui = $guiClassName::getInstance();
	}

	function onLoad()
	{
		$this->enableDatabase();
		$this->createTables();
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT | ServerEvent::ON_PLAYER_ALLIES_CHANGED);
		$this->enableTickerEvent();

		$this->hall = $this->storage->serverLogin.':'.$this->storage->server->password.'@'.$this->connection->getSystemInfo()->titleId;
		$this->modeClause = sprintf('title=%s', $this->db->quote($this->connection->getSystemInfo()->titleId));
		if(strpos($this->connection->getSystemInfo()->titleId, '@') === false)
				$this->modeClause .= sprintf(' AND script=%s', $this->db->quote($this->config->script));

		$this->setLobbyInfo();
		$playerList = Windows\PlayerList::Create();
		$playerList->setAlign('right');
		$playerList->setPosition(170, $this->gui->lobbyBoxPosY + 3);
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
		$lobbyWindow->setPosition(170, $this->gui->lobbyBoxPosY);
		$lobbyWindow->set($this->storage->server->name, $playersCount, $totalPlayerCount);
		$lobbyWindow->show();
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

			$this->createLabel($login, $this->gui->getMatchInProgressText());
			return;
		}

		$message = '';
		$player = PlayerInfo::Get($login);
		$message = ($player->ladderPoints ? $this->gui->getPlayerBackLabelPrefix() : '').$this->gui->getNotReadyText();
		$player->setAway(false);
		$player->setMatch();
		$player->ladderPoints = $this->matchMaker->getPlayerScore($login);
		$player->allies = $this->storage->getPlayerObject($login)->allies;
		$this->newPlayers[$login] = 20;

		$this->createLabel($login, $message);
		$this->onSetShortKey($login, false);

		$playerList = Windows\PlayerList::Create();
		$playerList->addPlayer($login);
		$playerList->redraw();

		$this->updateLobbyWindow();
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

		$playerList = Windows\PlayerList::Create();
		$playerList->removePlayer($login);
		$playerList->redraw();

		$this->updateLobbyWindow();
	}

	function onTick()
	{
		$matches = $this->matchMaker->run();
		foreach($matches as $match)
		{
			if(!($server = $this->getServer())) break;
			$this->prepareMatch($server, $match);
		}
		$this->updateLobbyWindow();
		$this->registerLobby();
		PlayerInfo::CleanUp();
		
		foreach($this->newPlayers as $login => $time)
		{
			if(--$time)
			{
				$this->newPlayers[$login] = $time;
			}
			else
			{
				unset($this->newPlayers[$login]);
				$this->onPlayerReady($login);
			}
		}

		foreach($this->countDown as $groupName => $value)
		{
			switch(--$value)
			{
				case -1:
					Windows\ForceManialink::Erase(Group::Get($groupName));
					Group::Erase($groupName);
					unset($this->countDown[$groupName]);
					break;
				case 0:
					Windows\ForceManialink::Create(Group::Get($groupName))->show();
				default:
					$this->countDown[$groupName] = $value;
			}
		}
	}

	function onPlayerReady($login)
	{
		if(array_key_exists($login, $this->newPlayers))
		{
			unset($this->newPlayers[$login]);
		}
		$player = PlayerInfo::Get($login);
		$player->setReady(true);
		$this->onSetShortKey($login, true);
		$this->createLabel($login, $this->gui->getReadyText());

		$playerList = Windows\PlayerList::Create();
		$playerList->setPlayer($login, true);
		$playerList->redraw();

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
		$this->createLabel($login, $this->gui->getNotReadyText());

		$playerList = Windows\PlayerList::Create();
		$playerList->setPlayer($login, false);
		$playerList->redraw();

		$this->updateLobbyWindow();
	}

	function onPlayerAlliesChanged($login)
	{
		PlayerInfo::Get($login)->allies = $this->storage->getPlayerObject($login)->allies;
	}

	protected function onSetShortKey($login, $ready)
	{
		$shortKey = Shortkey::Create($login);
		$callback = array($this, $ready ? 'onPlayerNotReady' : 'onPlayerReady');
		$shortKey->removeCallback($this->gui->actionKey);
		$shortKey->addCallback($this->gui->actionKey, $callback);
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

		$this->setLobbyInfo();

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
				'WHERE login = %s and players = %s', $this->db->quote($server), $this->db->quote(json_encode($players))
			)->fetchSingleValue(false);
		;
	}

	private function cancelMatch($login)
	{
		list($server, $players) = PlayerInfo::Get($login)->getMatch();
		$groupName = 'match-'.$server;
		Windows\ForceManialink::Erase(Group::Get($groupName));
		Group::Erase($groupName);
		unset($this->countDown[$groupName]);
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

	private function prepareMatch($server, $match)
	{
		$groupName = 'match-'.$server;
		if(in_array($match, $this->getCurrentMatch()) || array_key_exists($groupName, $this->countDown))
		{
			return;
		}
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
			PlayerInfo::Get($player)->setMatch($server, $match->players);
			$this->createLabel($player, $this->gui->getLaunchMatchText($match, $player), $this->countDown[$groupName] - 1);
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
				'WHERE '.$this->modeClause.' AND hall = %s', $this->db->quote($this->storage->serverLogin)
			)->fetchSingleValue(null);

		$playerCount = count($this->connection->getPlayerList(-1, 0));

		return $playerCount + $matchCount * $this->matchMaker->playerPerMatch;
	}

	private function getAvailableSlots()
	{
		return $this->db->execute(
				'SELECT COUNT(*) FROM Servers '.
				'WHERE '.$this->modeClause
			)->fetchSingleValue(null) * $this->matchMaker->playerPerMatch + $this->storage->server->currentMaxPlayers;
	}

	private function registerLobby()
	{
		$this->db->execute(
			'INSERT INTO Halls VALUES (%s, %d, %d, %s, %s) '.
			'ON DUPLICATE KEY UPDATE readyPlayers = VALUES(readyPlayers), connectedPlayers = VALUES(connectedPlayers)',
			$this->db->quote($this->storage->serverLogin), $this->getReadyPlayersCount(),
			count($this->connection->getPlayerList(-1, 0)), $this->db->quote($this->storage->server->name),
			$this->db->quote($this->hall)
		);
	}

	private function setLobbyInfo($enable = true)
	{
		if($enable)
		{
			$lobbyPlayers = $this->getTotalPlayerCount();
			$maxPlayers = $this->getAvailableSlots();
		}
		else
		{
			$lobbyPlayers = count($this->connection->getPlayerList(-1, 0));
			$maxPlayers = $this->storage->server->currentMaxPlayers;
		}
		$this->connection->setLobbyInfo($enable, $lobbyPlayers, $maxPlayers);
	}
	
	private function getCurrentMatch()
	{
		$matches = $this->db->query('SELECT players FROM Servers WHERE hall = %s and players IS NOT NULL')->fetchArrayOfSingleValues();
		return array_map('unserialize', $matches);
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
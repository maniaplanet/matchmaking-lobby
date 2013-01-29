<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\MatchControl;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLivePlugins\MatchMakingLobby\Windows;
use ManiaLivePlugins\MatchMakingLobby\Windows\Label;

class MatchControl extends \ManiaLive\PluginHandler\Plugin
{
	const ABORTING = -2;
	const WAITING = -1;
	const SLEEPING = 0;
	const DECIDING = 1;
	const PLAYING = 2;
	const OVER = 3;
	
	const PREFIX = 'Match$08fBot$000»$8f0 ';
	
	/** @var int */
	private $state = self::SLEEPING;
	/** @var \DateTime */
	private $nextTick = null;
	/** @var string[] */
	private $intervals = array();
	
	/** @var bool[string] */
	private $players = array();
	/** @var string */
	private $hall = null;
	/** @var \ManiaLivePlugins\MatchMakingLobby\LobbyControl\Match */
	private $match = null;

	function onInit()
	{
		$this->setVersion('0.1');
	}
	
	function onLoad()
	{
		$this->connection->cleanGuestList();
		$this->connection->addGuest('-_-');
		$this->connection->setHideServer(1);
		$this->connection->setMaxPlayers(0);
		$this->connection->setMaxSpectators(0);
		$this->connection->removeGuest('-_-');
		$this->nextTick = new \DateTime();
		$this->intervals = array(
			self::ABORTING => '1 minute',
			self::WAITING => '1 minute',
			self::SLEEPING => '5 seconds',
			self::DECIDING => '30 seconds',
			self::PLAYING => null,
			self::OVER => '10 seconds'
		);
		$this->enableDatabase();
		$this->enableTickerEvent();
		$this->createTables();
		
		$this->updateLobbyWindow();
	}
	
	function onTick()
	{
		if(new \DateTime() < $this->nextTick)
			return;
		if($this->state != self::SLEEPING)
		{
			$this->updateLobbyWindow();
		}
		switch($this->state)
		{
			case self::SLEEPING:
				if(!($next = $this->getNext()))
				{
					$this->live();
					$this->sleep();
					break;
				}
				$this->prepare($next->hall, $next->match);
				$this->wait();
				break;
			case self::DECIDING:
				$this->play();
				break;
			case self::WAITING:
				$this->cancel();
				break;
			case self::ABORTING:
				$this->cancel();
				break;
			case self::OVER:
				$this->end();
		}
	}
	
	function onPlayerConnect($login, $isSpectator)
	{
		$this->players[$login] = true;
		if($this->match->team1 && $this->match->team2)
		{
			$team = (array_search($login, $this->match->team1) ? 0 : 1);
			$this->connection->forcePlayerTeam($login, $team);
		}
		if($this->isEverybodyHere())
		{
			if($this->state == self::WAITING)
				$this->decide();
			else // if($this->state == self::ABORTING)
				$this->play();
		}
	}
	
	function onPlayerDisconnect($login)
	{
		$this->players[$login] = false;
		if(in_array($this->state, array(self::DECIDING, self::PLAYING)))
			$this->abort();
	}
	
	function onEndMatch($rankings, $winnerTeamOrMap)
	{
		if($this->state == self::PLAYING)
			$this->over();
		else if($this->state == self::DECIDING)
			$this->decide();
	}
	
	function onGiveUp($login)
	{
		$this->giveUp($login);
	}
	
	private function updateLobbyWindow()
	{
		$obj = $this->db->execute(
			'SELECT H.* FROM Halls H '.
			'INNER JOIN Servers S ON H.login = S.hall '.
			'WHERE S.login = %s',
			$this->db->quote($this->storage->serverLogin)
			)->fetchObject();
		if($obj)
		{
			$obj->totalPlayers = $this->db->execute(
			'SELECT COUNT(*) FROM Servers WHERE hall = %s',
			$this->db->quote($obj->login)
			)->fetchSingleValue();

			$lobbyWindow = Windows\LobbyWindow::Create();
			$lobbyWindow->setAlign('right', 'bottom');
			$lobbyWindow->setPosition(170, 45);
			$lobbyWindow->set($obj->name, $obj->readyPlayers, $obj->totalPlayers * 2 + $obj->connectedPlayers);
			$lobbyWindow->show();
		}
	}
	
	private function live()
	{
		$script = $this->connection->getScriptName();
		$script = preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script['CurrentValue']);
		$this->db->execute(
				'INSERT INTO Servers(login, title, script, lastLive) VALUES(%s, %s, %s, NOW()) '.
				'ON DUPLICATE KEY UPDATE title=VALUES(title), script=VALUES(script), lastLive=VALUES(lastLive)',
				$this->db->quote($this->storage->serverLogin),
				$this->db->quote($this->connection->getSystemInfo()->titleId),
				$this->db->quote($script)
			);
	}
	
	private function getNext()
	{
		$result = $this->db->execute(
				'SELECT H.backLink as hall, S.players as `match` FROM Servers  S '.
				'INNER JOIN Halls H ON S.hall = H.login '.
				'WHERE S.login=%s',
				$this->db->quote($this->storage->serverLogin)
			)->fetchObject();
		
		if(!$result || !$result->hall)
			return false;
		
		$result->match = json_decode($result->match);
		return $result;
	}
	
	private function prepare($hall, $match)
	{
		$this->hall = $hall;
		$this->players = array_fill_keys($match->players, false);
		$this->match = $match;
		Windows\ForceManialink::EraseAll();
		
		$giveUp = Windows\GiveUp::Create();
		$giveUp->setAlign('right');
		$giveUp->setPosition(160.1, 50);
		$giveUp->set(\ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, 'onGiveUp'), true));
		$giveUp->show();
		
		foreach($match->players as $login)
			$this->connection->addGuest($login, true);
		$this->connection->executeMulticall();
		
		$this->enableDedicatedEvents(ServerEvent::ON_PLAYER_CONNECT | ServerEvent::ON_PLAYER_DISCONNECT | ServerEvent::ON_END_MATCH);
		Label::EraseAll();
	}
	
	private function sleep()
	{
		$this->changeState(self::SLEEPING);
	}
	
	private function wait()
	{
		$this->changeState(self::WAITING);
	}
	
	private function abort()
	{
		Windows\GiveUp::EraseAll();
		$this->connection->chatSendServerMessage('A player quits... If he does not come back soon, match will be aborted.');
		$this->changeState(self::ABORTING);
	}
	
	private function giveUp($login)
	{
		$confirm = Label::Create();
		$confirm->setPosition(0, 40);
		$confirm->setMessage('Match over. You will be transfered back.');
		$confirm->show();
		Windows\GiveUp::EraseAll();
		$this->connection->chatSendServerMessage(sprintf('Match aborted because $<%s$> gave up.', 
			$this->storage->getPlayerObject($login)->nickName));
		$this->changeState(self::OVER);
	}
	
	private function cancel()
	{
		$confirm = Label::Create();
		$confirm->setPosition(0, 40);
		$confirm->setMessage('Match over. You will be transfered back.');
		$confirm->show();
		$this->connection->chatSendServerMessage('Match aborted.');
		$this->changeState(self::OVER);
	}
	
	private function decide()
	{
		if($this->state != self::DECIDING)
				$this->connection->chatSendServerMessage('Match is starting ,you still have time to change the map if you want.');
		$this->changeState(self::DECIDING);
	}
	
	private function play()
	{
		if($this->state == self::DECIDING)
			$this->connection->chatSendServerMessage('Time to change map is over!');
		else
			$this->connection->chatSendServerMessage('Player is back, match continues.');
		$this->changeState(self::PLAYING);
	}
	
	private function over()
	{
		Windows\GiveUp::EraseAll();
//		$this->connection->chatSendServerMessage('Match over! You will be transfered back to the lobby.');
		$this->changeState(self::OVER);
	}
	
	private function end()
	{
		$this->db->execute(
				'UPDATE Servers SET hall=NULL, players=NULL WHERE login=%s',
				$this->db->quote($this->storage->serverLogin)
			);
		
		$jumper = Windows\ForceManialink::Create();
		$jumper->set('maniaplanet://#qjoin='.$this->hall);
		$jumper->show();
		$this->connection->cleanGuestList();
		$this->sleep();
		usleep(20);
		$this->connection->restartMap();
	}
	
	private function changeState($state)
	{
		if($this->intervals[$state])
		{
			$this->nextTick = new \DateTime($this->intervals[$state]);
			$this->enableTickerEvent();
		}
		else
			$this->disableTickerEvent();
		
		$this->state = $state;
	}
	
	private function isEverybodyHere()
	{
		return count(array_filter($this->players)) == count($this->players);
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
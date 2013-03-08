<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Match;

use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLivePlugins\MatchMakingLobby\Windows;
use ManiaLivePlugins\MatchMakingLobby\Windows\Label;
use ManiaLivePlugins\MatchMakingLobby\Services;
use ManiaLivePlugins\MatchMakingLobby\GUI;

//TODO Convert chat message in big message on screen ?

class Plugin extends \ManiaLive\PluginHandler\Plugin
{

	/**
	 * Someone left
	 */
	const PLAYER_LEFT = -2;

	/**
	 * Waiting for all players to connect
	 */
	const WAITING = -1;
	const SLEEPING = 1;
	const DECIDING = 2;
	const PLAYING = 3;
	const OVER = 4;
	const PREFIX = 'Match$08fBot$000»$8f0 ';

	const PLAYER_STATE_QUITTER = -2;
	const PLAYER_STATE_NOT_CONNECTED = -1;
	const PLAYER_STATE_CONNECTED = 1;

	/** @var int */
	protected $state = self::SLEEPING;

	/** @var \DateTime */
	protected $nextTick = null;

	/** @var string[] */
	protected $intervals = array();

	/** @var bool[string] */
	protected $players = array();

	/** @var string */
	protected $lobby = null;

	/** @var string */
	protected $backLink = null;

	/** @var \ManiaLivePlugins\MatchMakingLobby\LobbyControl\Match */
	protected $match = null;

	/** @var GUI\AbstractGUI */
	protected $gui;

	/** @var int */
	protected $waitingTime = 0;

	/** @var int */
	protected $matchId;

	/** @var Services\LobbyService */
	protected $lobbyService;

	/** @var Services\MatchService */
	protected $matchService;

	/** @var string */
	protected $scriptName;

	function onInit()
	{
		$this->setVersion('0.3');
	}

	function onLoad()
	{
		//Check if the plugin is not connected on the lobby server
		if($this->isPluginLoaded('MatchMakingLobby/Lobby'))
		{
			throw new Exception('Lobby and match cannot be one the same server.');
		}

		//Set the maxPlayer number to 0 to avoid unwanted connection
		$this->connection->cleanGuestList();
		$this->connection->addGuest('-_-');
		$this->connection->setHideServer(1);
		$this->connection->setMaxPlayers(0);
		$this->connection->setMaxSpectators(0);
		$this->connection->removeGuest('-_-');
		$this->nextTick = new \DateTime();
		$this->intervals = array(
			self::PLAYER_LEFT => '1 minute',
			self::WAITING => '5 seconds',
			self::SLEEPING => '5 seconds',
			self::DECIDING => '30 seconds',
			self::PLAYING => null,
			self::OVER => '10 seconds'
		);

		$this->state = self::SLEEPING;

		$this->enableDatabase();
		$this->enableTickerEvent();
		$this->createTables();

		//Get the Script name
		$script = $this->connection->getScriptName();
		$this->scriptName = preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script['CurrentValue']);

		//Load services
		$titleIdString = $this->connection->getSystemInfo()->titleId;
		$this->matchService = new Services\MatchService($titleIdString, $this->scriptName);
		$this->lobbyService = new Services\LobbyService($titleIdString, $this->scriptName);

		//Get the GUI abstraction class
		$guiClassName = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance()->guiClassName ? : '\ManiaLivePlugins\MatchMakingLobby\GUI\\'.$this->scriptName;
		$this->setGui(new $guiClassName());

		//setup the Lobby info window
		$this->updateLobbyWindow();
	}

	//Core of the plugin
	function onTick()
	{
		if(new \DateTime() < $this->nextTick) return;
		if($this->state != self::SLEEPING)
		{
			$this->updateLobbyWindow();
		}
		switch($this->state)
		{
			case self::SLEEPING:
				$match = $this->matchService->get($this->storage->serverLogin);
				if ($match)
				{
					$this->prepare($match->backLink, $match->lobby, $match->match);
				}
				else
				{
					$this->matchService->registerServer($this->storage->serverLogin, $this->connection->getSystemInfo()->titleId, $this->scriptName);
					$this->sleep();
				}
				break;
			case self::WAITING:
				//Waiting for players, if Match change or cancel, change state and wait
				$this->waitingTime += 5;
				$match = $this->matchService->get($this->storage->serverLogin);
				\ManiaLive\Utilities\Logger::getLog('info')->write('waiting for '.$this->waitingTime);
				\ManiaLive\Utilities\Logger::getLog('info')->write(print_r($match,true));
				if($this->waitingTime > 120 || $match === false)
				{
					$this->cancel();
					break;
				}
				if($match->backLink != $this->backLink || $match->lobby != $this->lobby || $match->match != $this->match)
				{
					$this->prepare($match->backLink, $match->lobby ,$match->match);
					break;
				}
				$this->changeState(self::WAITING);
				break;
			case self::DECIDING:
				\ManiaLive\Utilities\Logger::getLog('info')->write('tick: DECIDING');
				$this->play();
				break;
			case self::PLAYER_LEFT:
				\ManiaLive\Utilities\Logger::getLog('info')->write('tick: PLAYER_LEFT');
				$this->cancel();
				break;
			case self::OVER:
				\ManiaLive\Utilities\Logger::getLog('info')->write('tick: OVER');
				$this->end();
		}
	}

	function onPlayerConnect($login, $isSpectator)
	{
		$this->players[$login] = static::PLAYER_STATE_CONNECTED;
		$this->forcePlayerTeam($login);
		\ManiaLive\Utilities\Logger::getLog('info')->write('player '.$login.' connected');
		switch ($this->state)
		{
			case static::SLEEPING:
				\ManiaLive\Utilities\Logger::getLog('error')->write('ERROR: incoherent state: player connected while match sleeping');
				break;
			case static::WAITING:
				if ($this->isEverybodyHere())
				{
					$this->decide();
				}
				break;
			case static::PLAYER_LEFT:
				if ($this->isEverybodyHere())
				{
					$this->play();
				}
				break;
			case static::DECIDING:
				\ManiaLive\Utilities\Logger::getLog('error')->write('ERROR: incoherent state: player connected while match deciding');
				break;
			case static::PLAYING:
				\ManiaLive\Utilities\Logger::getLog('error')->write('ERROR: incoherent state: player connected while match playing');
				break;
			case static::OVER:
				\ManiaLive\Utilities\Logger::getLog('error')->write('ERROR: incoherent state: player connected while match over');
				break;
		}
	}

	function onPlayerInfoChanged($playerInfo)
	{
		//TODO Find something to continue match at 2v3 for Elite
		//FIXME: Prevent player from changing team
//		if(in_array($this->state, array(self::DECIDING, self::PLAYING, self::PLAYER_LEFT))/* && $playerInfo['HasJoinedGame']*/)
//			$this->forcePlayerTeam($playerInfo['Login']);
	}

	function onPlayerDisconnect($login)
	{
		$this->players[$login] = static::PLAYER_STATE_NOT_CONNECTED;
		switch ($this->state)
		{
			case static::SLEEPING:
				\ManiaLive\Utilities\Logger::getLog('error')->write('ERROR: incoherent state: player disconnected while match sleeping');
				break;
			case static::WAITING:
				//nobreak
			case static::PLAYER_LEFT:
				//nobreak
			case static::DECIDING:
				//nobreak
			case static::PLAYING:
				$this->playerIllegalLeave($login);
				return;
			case static::OVER:
				return;
		}
	}

	function onEndMatch($rankings, $winnerTeamOrMap)
	{
		switch ($this->state)
		{
			case static::SLEEPING:
				\ManiaLive\Utilities\Logger::getLog('error')->write('ERROR: endMatch while sleeping');
				break;
			case static::WAITING:
				\ManiaLive\Utilities\Logger::getLog('error')->write('ERROR: endMatch while waiting');
				break;
			case static::DECIDING:
				$this->decide();
				break;
			case static::PLAYER_LEFT:
				//nobreak;
			case static::PLAYING:
				\ManiaLive\Utilities\Logger::getLog('info')->write('match ended fine');
				$this->over();
				break;
			case static::OVER:
				break;
		}
	}

	function onGiveUp($login)
	{
		$this->giveUp($login);
	}

	protected function updateLobbyWindow()
	{
		$obj = $this->lobbyService->get($this->lobby);
		if($obj)
		{
			$this->gui->updateLobbyWindow($obj->name, $obj->readyPlayers, $obj->connectedPlayers + $obj->playingPlayers, $obj->playingPlayers);
		}
	}

	protected function forcePlayerTeam($login)
	{
		$team = $this->match->getTeam($login);
		if ($team)
		{
			// -1 because :
			// 0 for server = team 1
			// 1 for server = team 2
			$this->connection->forcePlayerTeam($login, $team - 1);
		}
	}

	/**
	 * Prepare the server config to host a match
	 * Then wait players' connection
	 * @param string $backLink
	 * @param string $lobby
	 * @param Services\Match $match
	 */
	protected function prepare($backLink, $lobby, $match)
	{
		$this->backLink = $backLink;
		$this->lobby = $lobby;
		$this->players = array_fill_keys($match->players, static::PLAYER_STATE_NOT_CONNECTED);
		$this->match = $match;
		Windows\ForceManialink::EraseAll();

		$giveUp = Windows\GiveUp::Create();
		$giveUp->setAlign('right');
		$giveUp->setPosition(160.1, $this->gui->lobbyBoxPosY + 4.7);
		$giveUp->set(\ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, 'onGiveUp'), true));
		$giveUp->show();

		foreach($match->players as $login)
			$this->connection->addGuest((string)$login, true);
		$this->connection->executeMulticall();

		$this->enableDedicatedEvents(
			ServerEvent::ON_PLAYER_CONNECT |
			ServerEvent::ON_PLAYER_DISCONNECT |
			ServerEvent::ON_END_MATCH |
			ServerEvent::ON_PLAYER_INFO_CHANGED
		);
		Label::EraseAll();

		\ManiaLive\Utilities\Logger::getLog('info')->write('preparing match for '.$lobby.':'."\n".print_r($match,true));
		\ManiaLive\Utilities\Logger::getLog('info')->write('changing state to WAITING');
		$this->changeState(self::WAITING);
		$this->waitingTime = 0;
	}

	protected function sleep()
	{
		$this->changeState(self::SLEEPING);
	}

	protected function playerIllegalLeave($login)
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('player illegal leave '.$login);
		Windows\GiveUp::EraseAll();
		$this->connection->chatSendServerMessage('A player quits... If he does not come back soon, match will be aborted.');
		$this->changeState(self::PLAYER_LEFT);
		$this->player[$login] = static::PLAYER_STATE_QUITTER;
	}

	protected function giveUp($login)
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('player '.$login.' gave up. Changing state to OVER');
		$this->players[$login] = static::PLAYER_STATE_QUITTER;

		$confirm = Label::Create();
		$confirm->setPosition(0, 40);
		$confirm->setMessage('$900Match over. You will be transfered back.$z');
		$confirm->show();
		Windows\GiveUp::EraseAll();
		//FIXME: big message
		$this->connection->chatSendServerMessage(sprintf('Match aborted because $<%s$> gave up.',
				$this->storage->getPlayerObject($login)->nickName));
		$quitterService = new Services\QuitterService($this->lobby);
		$quitterService->register($login);

		$this->changeState(self::OVER);
	}

	protected function cancel()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('Cancel match. Changing state to OVER');
		//FIXME: maybe
		$quitterService = new Services\QuitterService($this->lobby);
		foreach($this->players as $login => $state)
		{
			if($state == static::PLAYER_STATE_QUITTER)
			{
				$quitterService->register($login);
			}
		}

		$confirm = Label::Create();
		$confirm->setPosition(0, 40);
		$confirm->setMessage('Match over. You will be transfered back.');
		$confirm->show();

		$this->connection->chatSendServerMessage('Match aborted.');

		$this->changeState(self::OVER);
	}

	protected function decide()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('Changing state to DECIDE');
		if($this->state != self::DECIDING)
				$this->connection->chatSendServerMessage('Match is starting ,you still have time to change the map if you want.');
		$this->changeState(self::DECIDING);
	}

	protected function play()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('Changing state to PLAY');
		//FIXME: ugly
		$this->db->execute(
			'INSERT INTO PlayedMatchs (`server`, `title`, `script`, `match`, `playedDate`) VALUES (%s, %s, %s, %s, NOW())',
			$this->db->quote($this->storage->serverLogin),
			$this->db->quote($this->connection->getSystemInfo()->titleId),
			$this->db->quote($this->scriptName),
			$this->db->quote(json_encode($this->match))
		);

		$this->matchId = $this->db->insertID();

		\ManiaLive\Utilities\Logger::getLog('info')->write('Starting match:'.$this->matchId);;

		$giveUp = Windows\GiveUp::Create();
		$giveUp->setAlign('right');
		$giveUp->setPosition(160.1, $this->gui->lobbyBoxPosY + 4.7);
		$giveUp->set(\ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, 'onGiveUp'), true));
		$giveUp->show();

		if($this->state == self::DECIDING)
		{
			$this->connection->chatSendServerMessage('Time to change map is over!');
		}
		else
		{
			$this->connection->chatSendServerMessage('Player is back, match continues.');
		}

		$this->changeState(self::PLAYING);
	}

	protected function over()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('Changing state to OVER');
		Windows\GiveUp::EraseAll();
//		$this->connection->chatSendServerMessage('Match over! You will be transfered back to the lobby.');
		$this->changeState(self::OVER);
	}

	protected function end()
	{
		$this->matchService->removeMatch($this->storage->serverLogin);
		\ManiaLive\Utilities\Logger::getLog('info')->write('Match ended');
		$jumper = Windows\ForceManialink::Create();
		$jumper->set('maniaplanet://#qjoin='.$this->backLink);
		$jumper->show();
		$this->connection->cleanGuestList();
		$this->sleep();
		usleep(20);
		try
		{
			$this->connection->restartMap();
		}
		catch(\Exception $e)
		{

		}
	}

	protected function changeState($state)
	{
		if($this->intervals[$state])
		{
			$this->nextTick = new \DateTime($this->intervals[$state]);
			$this->enableTickerEvent();
		}
		else $this->disableTickerEvent();

		$this->state = $state;
	}

	protected function isEverybodyHere()
	{
		//TODO: usage of 0 instead of self::PLAYER_STATE_*
		return count(array_filter($this->players, function ($p) { return $p > 0; })) == count($this->players);
	}

	protected function setGui(GUI\AbstractGUI $GUI)
	{
		$this->gui = $GUI;
	}

	protected function createTables()
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
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

	const ABORTING = -2;
	const WAITING = -1;
	const SLEEPING = 1;
	const DECIDING = 2;
	const PLAYING = 3;
	const OVER = 4;
	const PREFIX = 'Match$08fBot$000»$8f0 ';

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
	protected $matchId = 0;

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
			self::ABORTING => '1 minute',
			self::WAITING => '5 seconds',
			self::SLEEPING => '5 seconds',
			self::DECIDING => '30 seconds',
			self::PLAYING => null,
			self::OVER => '10 seconds'
		);

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
				if (!$match)
				{
					$this->matchService->registerServer($this->storage->serverLogin, $this->connection->getSystemInfo()->titleId, $this->scriptName);
					$this->sleep();
					break;
				}
				else
				{
					$this->prepare($match->backLink, $match->lobby, $match->match);
				}
				$this->wait();
				break;
			case self::DECIDING:
				$this->play();
				break;
			case self::WAITING:
				//Waiting for players, if Match change or cancel, change state and wait
				$this->waitingTime += 5;
				$current = $this->matchService->get($this->storage->serverLogin);
				if($this->waitingTime > 120 || $current === false)
				{
					$this->cancel();
					break;
				}
				if($current->backLink != $this->backLink || $current->lobby != $this->lobby || $current->match != $this->match)
				{
					$this->prepare($current->backLink, $current->lobby ,$current->match);
					$this->wait();
					break;
				}
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
		$this->forcePlayerTeam($login);
		if($this->isEverybodyHere())
		{
			if($this->state == self::WAITING) $this->decide();
			elseif($this->state == self::ABORTING) $this->play();
		}
	}

	function onPlayerInfoChanged($playerInfo)
	{
		//TODO Find something to continue match at 2v3 for Elite
		if(in_array($this->state, array(self::DECIDING, self::PLAYING, self::ABORTING))/* && $playerInfo['HasJoinedGame']*/)
			$this->forcePlayerTeam($playerInfo['Login']);
	}

	function onPlayerDisconnect($login)
	{
		$this->players[$login] = (in_array($this->state, array(self::DECIDING, self::PLAYING)) ? -1 : false);
		if(in_array($this->state, array(self::DECIDING, self::PLAYING))) $this->abort();
	}

	function onEndMatch($rankings, $winnerTeamOrMap)
	{
		if($this->state == self::PLAYING || $this->state == self::ABORTING) $this->over();
		elseif($this->state == self::DECIDING) $this->decide();
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
		if($this->match->team1 && $this->match->team2)
		{
			$team = (array_keys($this->match->team1, $login) ? 0 : 1);
			$this->connection->forcePlayerTeam($login, $team);
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
		$this->players = array_fill_keys($match->players, false);
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
	}

	protected function sleep()
	{
		$this->changeState(self::SLEEPING);
	}

	protected function wait()
	{
		$this->changeState(self::WAITING);
		$this->waitingTime = 0;
	}

	protected function abort()
	{
		Windows\GiveUp::EraseAll();
		$this->connection->chatSendServerMessage('A player quits... If he does not come back soon, match will be aborted.');
		$this->changeState(self::ABORTING);
	}

	protected function giveUp($login)
	{
		$confirm = Label::Create();
		$confirm->setPosition(0, 40);
		$confirm->setMessage('Match over. You will be transfered back.');
		$confirm->show();
		Windows\GiveUp::EraseAll();
		$this->connection->chatSendServerMessage(sprintf('Match aborted because $<%s$> gave up.',
				$this->storage->getPlayerObject($login)->nickName));
		$quitterService = new Services\QuitterService($this->lobby);
		$quitterService->register($login);
		$this->changeState(self::OVER);
	}

	protected function cancel()
	{
		if($this->state == self::ABORTING)
		{
			$quitterService = new Services\QuitterService($this->lobby);
			foreach($this->players as $login => $value)
			{
				if($value == -1)
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
		if($this->state != self::DECIDING)
				$this->connection->chatSendServerMessage('Match is starting ,you still have time to change the map if you want.');
		if(!$this->matchId)
		{
			$this->db->execute(
				'INSERT INTO PlayedMatchs (`server`, `title`, `script`, `match`, `playedDate`) VALUES (%s, %s, %s, %s, NOW())',
				$this->db->quote($this->storage->serverLogin),
				$this->db->quote($this->connection->getSystemInfo()->titleId),
				$this->db->quote($this->scriptName),
				$this->db->quote(json_encode($this->match))
			);
		}
		$this->changeState(self::DECIDING);
	}

	protected function play()
	{
		$giveUp = Windows\GiveUp::Create();
		$giveUp->setAlign('right');
		$giveUp->setPosition(160.1, $this->gui->lobbyBoxPosY + 4.7);
		$giveUp->set(\ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, 'onGiveUp'), true));
		$giveUp->show();

		if($this->state == self::DECIDING) $this->connection->chatSendServerMessage('Time to change map is over!');
		else $this->connection->chatSendServerMessage('Player is back, match continues.');
		$this->changeState(self::PLAYING);
	}

	protected function over()
	{
		Windows\GiveUp::EraseAll();
//		$this->connection->chatSendServerMessage('Match over! You will be transfered back to the lobby.');
		$this->changeState(self::OVER);
	}

	protected function end()
	{
		$this->matchService->removeMatch($this->storage->serverLogin);

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
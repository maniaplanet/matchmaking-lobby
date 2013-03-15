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
	const WAITING_REPLACEMENT = 5;
	const PREFIX = 'Match$08fBot$000»$8f0 ';

	/** @var int */
	protected $state = self::SLEEPING;

	/** @var \DateTime */
	protected $nextTick = null;

	/** @var string[] */
	protected $intervals = array();

	/**
	 * Value one of Services\PlayerInfo::PLAYER_STATE_*
	 * @var int[string]
	 */
	protected $players = array();

	/** @var Services\Lobby */
	protected $lobby = null;

	/** @var \ManiaLivePlugins\MatchMakingLobby\LobbyControl\Match */
	protected $match = null;

	/** @var GUI\AbstractGUI */
	protected $gui;

	/** @var int */
	protected $waitingTime = 0;

	/** @var int */
	protected $matchId;

	/** @var Services\MatchMakingService */
	protected $matchMakingService;

	/** @var string */
	protected $scriptName;
	
	/** @var string */
	protected $titleIdString;

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
			self::OVER => '10 seconds',
			self::WAITING_REPLACEMENT => '1 seconds'
		);

		$this->state = self::SLEEPING;

		$this->enableTickerEvent();

		//Get the Script name
		$script = $this->connection->getScriptName();
		$this->scriptName = preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script['CurrentValue']);
		$this->titleIdString = $this->connection->getSystemInfo()->titleId;

		//Load services
		$this->matchMakingService = new Services\MatchMakingService();
		$this->matchMakingService->createTables();
		
		$config = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance();
		$this->lobby = $this->matchMakingService->getLobby($config->lobbyLogin);
		
		//Get the GUI abstraction class
		$guiClassName = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance()->guiClassName ? : '\ManiaLivePlugins\MatchMakingLobby\GUI\\'.$this->scriptName;
		$this->setGui(new $guiClassName());

		//setup the Lobby info window
		$this->updateLobbyWindow();
	}
	
	function onUnload()
	{
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::FINISHED);
		$this->end();
		parent::onUnload();
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
				//Waiting for a match in database
				$match = $this->matchMakingService->getMatchInfo($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
				if ($match)
				{
					$this->prepare($match);
				}
				else
				{
					$this->matchMakingService->registerMatchServer(
						$this->storage->serverLogin, 
						$this->lobby->login, 
						$this->state,
						$this->scriptName,
						$this->titleIdString
					);
					$this->sleep();
				}
				break;
			case self::WAITING:
				//Waiting for players, if Match change or cancel, change state and wait
				$this->waitingTime += 5;
				$match = $this->matchMakingService->getMatchInfo($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
				if($this->waitingTime > 120)
				{
					\ManiaLive\Utilities\Logger::getLog('info')->write('Waiting time over');
					array_walk($this->players, function($state) { if ($state == -1) { return -2; }});
					$this->cancel();
					break;
				}
				if($match === false)
				{
					\ManiaLive\Utilities\Logger::getLog('info')->write('Match was prepared but not in database anymore (canceled on lobby ?');
					$this->cancel();
					break;
				}
				if($match->match != $this->match || $match->matchId != $this->matchId)
				{
					$this->prepare($match);
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
				$this->waitReplacement();
				break;
			case self::WAITING_REPLACEMENT:
				if(++$this->waitingTime < 5)
				{
					//TODO get Replacement
					$this->updatePlayerList();
				}
				else
				{
					$this->cancel();
				}
				break;
			case self::OVER:
				\ManiaLive\Utilities\Logger::getLog('info')->write('tick: OVER');
				$this->end();
				break;
		}
	}

	function onPlayerConnect($login, $isSpectator)
	{
		$this->players[$login] = Services\PlayerInfo::PLAYER_STATE_CONNECTED;
		$this->matchMakingService->updatePlayerState($login, $this->matchId, $this->players[$login]);
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
			case static::WAITING_REPLACEMENT:
				//nobreak
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
		if(in_array($this->state, array(self::DECIDING, self::PLAYING, self::PLAYER_LEFT))/* && $playerInfo['HasJoinedGame']*/)
			$this->forcePlayerTeam($playerInfo['Login']);
	}

	function onPlayerDisconnect($login)
	{
		$this->players[$login] = Services\PlayerInfo::PLAYER_STATE_NOT_CONNECTED;
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
			case static::WAITING_REPLACEMENT:
				//nobreak
			case static::PLAYING:
				$this->playerIllegalLeave($login);
				break;
			case static::OVER:
				break;
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
			case static::WAITING_REPLACEMENT:
				//nobreak
			case static::PLAYING:
				\ManiaLive\Utilities\Logger::getLog('info')->write('SUCCESS: onEndMatch while playing');
				$this->matchMakingService->updateMatchState($this->matchId, Services\Match::FINISHED);
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
		$this->lobby = $this->matchMakingService->getLobby($this->lobby->login);
		$playingPlayers = 0;
		$this->gui->updateLobbyWindow($this->lobby->name, $this->lobby->readyPlayers, $this->lobby->connectedPlayers + $playingPlayers, $playingPlayers);
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
	 * @param Services\MatchInfo $matchInfo
	 */
	protected function prepare($matchInfo)
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write(print_r($matchInfo, true));
		$this->players = array_fill_keys($matchInfo->match->players, Services\PlayerInfo::PLAYER_STATE_NOT_CONNECTED);
		$this->match = $matchInfo->match;
		$this->matchId = $matchInfo->matchId;
		Windows\ForceManialink::EraseAll();

		$giveUp = Windows\GiveUp::Create();
		$giveUp->setAlign('right');
		$giveUp->setPosition(160.1, $this->gui->lobbyBoxPosY + 4.7);
		$giveUp->set(\ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, 'onGiveUp'), true));
		$giveUp->show();

		foreach($matchInfo->match->players as $login)
			$this->connection->addGuest((string)$login, true);
		$this->connection->executeMulticall();

		$this->enableDedicatedEvents(
			ServerEvent::ON_PLAYER_CONNECT |
			ServerEvent::ON_PLAYER_DISCONNECT |
			ServerEvent::ON_END_MATCH |
			ServerEvent::ON_PLAYER_INFO_CHANGED
		);
		Label::EraseAll();

		\ManiaLive\Utilities\Logger::getLog('info')->write(sprintf('Preparing match for %s (%s)',$this->lobby->login, implode(',', array_keys($this->players))));
		$this->changeState(self::WAITING);
	}

	protected function sleep()
	{
		$this->changeState(self::SLEEPING);
	}

	protected function playerIllegalLeave($login)
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('Player illegal leave: '.$login);
		Windows\GiveUp::EraseAll();

		$label = Label::Create();
		$label->setPosition(0, 40);
		$label->setMessage('A player left. Stay online until you are transfered back or you will be banned.');
		$label->show();

		$this->changeState(self::PLAYER_LEFT);
		$this->players[$login] = Services\PlayerInfo::PLAYER_STATE_QUITTER;
		$this->matchMakingService->updatePlayerState($login, $this->matchId, $this->players[$login]);
	}

	protected function giveUp($login)
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('Player '.$login.' gave up. Changing state to OVER');
		$this->players[$login] = Services\PlayerInfo::PLAYER_STATE_GIVE_UP;
		
		$this->matchMakingService->updatePlayerState($login, $this->matchId, $this->players[$login]);
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::PLAYER_GAVE_UP);

		$confirm = Label::Create();
		$confirm->setPosition(0, 40);
		$confirm->setMessage('$900Match over. You will be transfered back.$z');
		$confirm->show();
		Windows\GiveUp::EraseAll();
		//FIXME: big message
		$this->connection->chatSendServerMessage(sprintf('Match aborted because $<%s$> gave up.',
				$this->storage->getPlayerObject($login)->nickName));

		$this->changeState(self::OVER);
	}

	protected function cancel()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('cancel()');
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::PLAYER_LEFT);

		$confirm = Label::Create();
		$confirm->setPosition(0, 40);
		$confirm->setMessage('Match over. You will be transfered back.');
		$confirm->show();

		$this->connection->chatSendServerMessage('Match aborted.');

		$this->over();
	}

	protected function decide()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('decide()');
		if($this->state != self::DECIDING)
		{
				$confirm = Label::Create();
				$confirm->setPosition(0, 40);
				$confirm->setMessage('Match will start soon.');
				$confirm->show();

				$this->connection->chatSendServerMessage('Match is starting ,you still have time to change the map if you want.');
		}

		$this->changeState(self::DECIDING);
	}

	protected function play()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('play()');
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::PLAYING);
		
		Label::EraseAll();

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
		\ManiaLive\Utilities\Logger::getLog('info')->write('over()');
		Windows\GiveUp::EraseAll();
//		$this->connection->chatSendServerMessage('Match over! You will be transfered back to the lobby.');
		$this->changeState(self::OVER);
	}
	
	protected function waitReplacement()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('waitReplacement()');
		$this->changeState(self::WAITING_REPLACEMENT);
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::WAITING_REPLACEMENT);
	}
	
	protected function updatePlayerList()
	{
		$matchInfo = $this->matchMakingService->getMatchInfo($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		if($matchInfo && $matchInfo->match != $this->match)
		{
			$newPlayers = array_diff($matchInfo->match->players, $this->match->players);
			$this->connection->cleanGuestList(true);
			foreach($matchInfo->match->players as $player)
			{
				$this->connection->addGuest($player, true);
				if(in_array($player, $newPlayers))
				{
					$this->players[$player] = Services\PlayerInfo::PLAYER_STATE_NOT_CONNECTED;
				}
			}
			//TODO Clean player list  ???
			$this->connection->executeMulticall();
			$this->match = $matchInfo->match;
		}
	}

	/**
	 * Free the match for the lobby
	 */
	protected function end()
	{
		\ManiaLive\Utilities\Logger::getLog('info')->write('end()');

		$jumper = Windows\ForceManialink::Create();
		$jumper->set('maniaplanet://#qjoin='.$this->lobby->backLink);
		$jumper->show();
		$this->connection->cleanGuestList();
		usleep(2000);
		try
		{
			$this->connection->nextMap();
		}
		catch(\Exception $e)
		{

		}
		usleep(5000);
		$this->sleep();
	}

	protected function changeState($state)
	{
		if($this->intervals[$state])
		{
			$this->nextTick = new \DateTime($this->intervals[$state]);
			$this->enableTickerEvent();
		}
		else $this->disableTickerEvent();

		if ($this->state != $state)
		{
			\ManiaLive\Utilities\Logger::getLog('info')->write(sprintf('State: %d', $state));
		}

		$this->state = $state;
		$this->waitingTime = 0;
	}

	protected function isEverybodyHere()
	{
		//TODO: usage of 0 instead of Services\PlayerInfo::PLAYER_STATE_*
		return count(array_filter($this->players, function ($p) { return $p > 0; })) == count($this->players);
	}

	protected function setGui(GUI\AbstractGUI $GUI)
	{
		$this->gui = $GUI;
	}
}

?>
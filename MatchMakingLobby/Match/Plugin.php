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

/**
 * Plugin to load on the Matches servers
 */
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

	/**
	 * Waiting for a match
	 */
	const SLEEPING = 1;

	/**
	 * All players are connected. Waiting some more time
	 */
	const DECIDING = 2;

	/**
	 * Playing match
	 */
	const PLAYING = 3;

	/**
	 * Match ended (well or not)
	 */
	const OVER = 4;

	/**
	 * Waiting for backup from the lobby
	 */
	const WAITING_BACKUPS = 5;

	const PREFIX = 'Match$08fBot$000»$8f0 ';

	const TIME_WAITING_CONNECTION = 105;
	const TIME_WAITING_BACKUP = 20;

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

	/** @var Services\Match */
	protected $match = null;

	/** @var GUI\AbstractGUI */
	protected $gui;

	/** @var int */
	protected $waitingTime = 0;

	/** @var int */
	protected $waitingBackupTime = 0;

	/** @var int */
	protected $matchId;

	/** @var Services\MatchMakingService */
	protected $matchMakingService;

	/** @var string */
	protected $scriptName;

	/** @var string */
	protected $titleIdString;

	/**
	 * @var \ManiaLivePlugins\MatchMakingLobby\Config
	 */
	protected $config;

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
			self::PLAYER_LEFT => '40 seconds',
			self::WAITING => '5 seconds',
			self::SLEEPING => '5 seconds',
			self::DECIDING => '30 seconds',
			self::PLAYING => '15 seconds',
			self::OVER => '10 seconds',
			self::WAITING_BACKUPS => '1 seconds'
		);

		$this->config = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance();

		$this->state = self::SLEEPING;

		$this->enableTickerEvent();

		//Get the Script name
		$script = $this->connection->getScriptName();
		$this->scriptName = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance()->script ? : preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script['CurrentValue']);
		$this->titleIdString = $this->connection->getSystemInfo()->titleId;

		//Set needed rules to run the lobny
		$matchSettingsClass = $this->config->matchSettingsClassName ? : '\ManiaLivePlugins\MatchMakingLobby\MatchSettings\\'.$this->scriptName;
		/* @var $matchSettings \ManiaLivePlugins\MatchMakingLobby\MatchSettings\MatchSettings */
		if (!class_exists($matchSettingsClass))
		{
			throw new Exception(sprintf("Can't find class %s. You should set up the config : ManiaLivePlugins\MatchMakingLobby\Config.matchSettingsClassName",$matchSettingsClass));
		}

		$matchSettings = new $matchSettingsClass();
		$settings = $matchSettings->getMatchScriptSettings();
		$this->connection->setModeScriptSettings($settings);

		//Load services
		$this->matchMakingService = new Services\MatchMakingService();
		$this->matchMakingService->createTables();


		$this->lobby = $this->matchMakingService->getLobby($this->config->lobbyLogin);

		//Get the GUI abstraction class
		$guiClassName = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance()->guiClassName ? : '\ManiaLivePlugins\MatchMakingLobby\GUI\\'.$this->scriptName;
		$this->setGui(new $guiClassName());

		//setup the Lobby info window
		$this->updateLobbyWindow();

//		$this->connection->customizeQuitDialog(null, '#qjoin='.$this->lobby->backLink, false);
	}

	function onUnload()
	{
		if($this->matchMakingService instanceof Services\MatchMakingService && $this->matchId)
		{
			$this->matchMakingService->updateMatchState($this->matchId, Services\Match::FINISHED);
			$this->end();
		}
//		$this->connection->customizeQuitDialog();
		parent::onUnload();
	}

	//Core of the plugin
	function onTick()
	{
		if($this->state != self::SLEEPING)
		{
			$this->updateLobbyWindow();
		}
		if(new \DateTime() < $this->nextTick) return;

		$config = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance();
		switch($this->state)
		{
			case self::SLEEPING:
				//Waiting for a match in database
				$match = $this->matchMakingService->getServerCurrentMatch($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
				if ($match)
				{
					$this->prepare($match);
				}
				else
				{
					$this->sleep();
				}
				break;
			case self::WAITING:
				//Waiting for players, if Match change or cancel, change state and wait
				$this->waitingTime += 5;
				\ManiaLive\Utilities\Logger::debug(sprintf('waiting time %d',$this->waitingTime));
				$match = $this->matchMakingService->getServerCurrentMatch($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
				if($this->waitingTime > static::TIME_WAITING_CONNECTION)
				{
					\ManiaLive\Utilities\Logger::debug('Waiting time over');
					foreach($this->players as $login => $state)
					{
						if($state == Services\PlayerInfo::PLAYER_STATE_NOT_CONNECTED)
						{
							$this->updateMatchPlayerState($login,Services\PlayerInfo::PLAYER_STATE_QUITTER);
						}
					}
					$this->waitBackups();
					break;
				}
				if($match === false)
				{
					\ManiaLive\Utilities\Logger::debug('Match was prepared but not in database anymore (canceled on lobby ?');
					$this->cancel();
					break;
				}
				if($match != $this->match || $match->id != $this->matchId)
				{
					$this->prepare($match);
					break;
				}
				$this->changeState(self::WAITING);
				break;
			case self::DECIDING:
				\ManiaLive\Utilities\Logger::debug('tick: DECIDING');
				$this->play();
				break;
			case static::PLAYING:
				$this->updateLobbyWindow();
				$this->changeState(static::PLAYING);
				break;
			case self::PLAYER_LEFT:
				\ManiaLive\Utilities\Logger::debug('tick: PLAYER_LEFT');
				$this->waitBackups();
				break;
			case self::WAITING_BACKUPS:
				switch($config->waitingForBackups)
				{
					case 0:
						$isWaitingTimeOver = true;
						break;
					case 2:
						$isWaitingTimeOver = false;
						break;
					case 1:
						//nobreak
					default:
						$isWaitingTimeOver = (++$this->waitingBackupTime > static::TIME_WAITING_BACKUP);
						break;
				}
				if($isWaitingTimeOver)
				{
					\ManiaLive\Utilities\Logger::debug('tick: WAITING_BACKUPS over');
					$this->cancel();
				}
				else
				{
					$match = $this->matchMakingService->getServerCurrentMatch($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
					if($match && $match != $this->match)
					{
						$this->updatePlayerList($match);
					}
					$this->changeState(self::WAITING_BACKUPS);;
				}
				break;
			case self::OVER:
				\ManiaLive\Utilities\Logger::debug('tick: OVER');
				$this->end();
		}
	}

	function onPlayerConnect($login, $isSpectator)
	{
		$this->updateMatchPlayerState($login, Services\PlayerInfo::PLAYER_STATE_CONNECTED);
		$this->forcePlayerTeam($login);
		\ManiaLive\Utilities\Logger::debug('player '.$login.' connected');
		switch ($this->state)
		{
			case static::SLEEPING:
				\ManiaLive\Utilities\Logger::debug('ERROR: incoherent state: player connected while match sleeping');
				break;
			case static::WAITING:
				if ($this->isEverybodyHere())
				{
					$this->decide();
				}
				break;
			case static::WAITING_BACKUPS:
				//nobreak
			case static::PLAYER_LEFT:
				\ManiaLive\Utilities\Logger::debug(sprintf('isEveryBodyHere -> %d',$this->isEverybodyHere()));
				if ($this->isEverybodyHere())
				{
					$this->play();
				}
				break;
			case static::DECIDING:
				\ManiaLive\Utilities\Logger::debug('ERROR: incoherent state: player connected while match deciding');
				break;
			case static::PLAYING:
				\ManiaLive\Utilities\Logger::debug('ERROR: incoherent state: player connected while match playing');
				break;
			case static::OVER:
				\ManiaLive\Utilities\Logger::debug('ERROR: incoherent state: player connected while match over');
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
		switch ($this->state)
		{
			case static::WAITING:
				//nobreak
			case static::PLAYER_LEFT:
				//nobreak
			case static::DECIDING:
				//nobreak
			case static::WAITING_BACKUPS:
				//nobreak
			case static::PLAYING:
				// If a player gave up, no need to punish him!
				if(array_key_exists($login, $this->players) && $this->players[$login] != Services\PlayerInfo::PLAYER_STATE_GIVE_UP)
				{
					$this->playerIllegalLeave($login);
				}
				break;
			case static::SLEEPING:
				\ManiaLive\Utilities\Logger::debug('ERROR: incoherent state: player disconnected while match sleeping');
				//nobreak
			case static::OVER:
				if(array_key_exists($login, $this->players))
				{
					$this->players[$login] = Services\PlayerInfo::PLAYER_STATE_NOT_CONNECTED;
				}
				break;
		}

	}

	function onEndMatch($rankings, $winnerTeamOrMap)
	{
		\ManiaLive\Utilities\Logger::debug($this->connection->checkEndMatchCondition());
		switch ($this->state)
		{
			case static::SLEEPING:
				\ManiaLive\Utilities\Logger::debug('ERROR: endMatch while sleeping');
				break;
			case static::WAITING:
				\ManiaLive\Utilities\Logger::debug('ERROR: endMatch while waiting');
				break;
			case static::DECIDING:
				$this->decide();
				break;
			case static::WAITING_BACKUPS:
				\ManiaLive\Utilities\Logger::debug('SUCCESS: onEndMatch with missing players');
				$this->registerRankings($rankings);
				$this->matchMakingService->updateMatchState($this->matchId, Services\Match::FINISHED_WAITING_BACKUPS);
				$this->over();
				break;
			case static::PLAYER_LEFT:
				//nobreak;
				//nobreak
			case static::PLAYING:
				\ManiaLive\Utilities\Logger::debug('SUCCESS: onEndMatch while playing');
				$this->registerRankings($rankings);
				$this->matchMakingService->updateMatchState($this->matchId, Services\Match::FINISHED);
				$this->over();
				break;
			case static::OVER:
				break;
		}
	}

	function onPlayerGiveUp($login)
	{
		$this->giveUp($login);
	}

	protected function updateLobbyWindow()
	{
		$this->lobby = $this->matchMakingService->getLobby($this->lobby->login);
		$playingPlayers = $this->matchMakingService->getPlayersPlayingCount($this->lobby->login);
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
	 * @param Services\Match $match
	 */
	protected function prepare($match)
	{
		\ManiaLive\Utilities\Logger::debug($match);
		$this->players = array_fill_keys($match->players, Services\PlayerInfo::PLAYER_STATE_NOT_CONNECTED);
		$this->match = $match;
		$this->matchId = $match->id;
		Windows\ForceManialink::EraseAll();

		$giveUp = Windows\GiveUp::Create();
		$giveUp->setAlign('right');
		$giveUp->setPosition(160.1, $this->gui->lobbyBoxPosY + 4.7);
		$giveUp->set(\ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, 'onPlayerGiveUp'), true));
		$giveUp->show();

		foreach($match->players as $login)
		{
			$this->connection->addGuest((string)$login, true);
		}
		$this->connection->restartMap(false, true);
		$this->connection->executeMulticall();

		$this->enableDedicatedEvents(
			ServerEvent::ON_PLAYER_CONNECT |
			ServerEvent::ON_PLAYER_DISCONNECT |
			ServerEvent::ON_END_MATCH |
			ServerEvent::ON_PLAYER_INFO_CHANGED
		);

		\ManiaLive\Utilities\Logger::debug(sprintf('Preparing match for %s (%s)',$this->lobby->login, implode(',', array_keys($this->players))));
		$this->changeState(self::WAITING);
		$this->waitingTime = 0;
//		$this->connection->setForcedTeams(true);
	}

	protected function sleep()
	{
		$jumper = Windows\ForceManialink::Create();
		$jumper->set('maniaplanet://#qjoin='.$this->lobby->backLink);
		$jumper->show();
		$this->changeState(self::SLEEPING);
	}

	protected function playerIllegalLeave($login)
	{
		\ManiaLive\Utilities\Logger::debug('Player illegal leave: '.$login);
		Windows\GiveUp::EraseAll();

		$this->gui->createLabel($this->gui->getIllegalLeaveText(), null, null, false, false);


		$this->changeState(self::PLAYER_LEFT);
		$this->updateMatchPlayerState($login, Services\PlayerInfo::PLAYER_STATE_QUITTER);
	}

	protected function giveUp($login)
	{
		\ManiaLive\Utilities\Logger::debug('Player '.$login.' gave up.');

		$this->updateMatchPlayerState($login, Services\PlayerInfo::PLAYER_STATE_GIVE_UP);

		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::WAITING_BACKUPS);

		$this->gui->createLabel($this->gui->getGiveUpText(), null, null, false, false);

		$jumper = Windows\ForceManialink::Create($login);
		$jumper->set('maniaplanet://#qjoin='.$this->lobby->backLink);
		$jumper->show();

		Windows\GiveUp::Erase($login);

		$this->waitBackups();
	}

	protected function cancel()
	{
		\ManiaLive\Utilities\Logger::debug('cancel()');
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::PLAYER_LEFT);

		$this->gui->createLabel($this->gui->getMatchoverText(), null, null, false, false);

		$this->connection->chatSendServerMessage(static::PREFIX.'Match aborted.');

		$this->over();
	}

	protected function decide()
	{
		\ManiaLive\Utilities\Logger::debug('decide()');
		if($this->state != self::DECIDING)
		{
			$this->gui->createLabel($this->gui->getDecidingText(), null, null, false, false);

			$this->connection->chatSendServerMessage(static::PREFIX.' Match is starting, you still have time to change the map if you want.');
			$ratios = array(
				array('Command' => 'nextMap', 'Ratio' => 0.5),
				array('Command' => 'jumpToMapIndex', 'Ratio' => 0.5),
			);
			$this->connection->setCallVoteRatios($ratios);
		}

		$this->changeState(self::DECIDING);
	}

	protected function play()
	{
		\ManiaLive\Utilities\Logger::debug('play()');
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::PLAYING);

		Label::EraseAll();

		$giveUp = Windows\GiveUp::Create();
		$giveUp->setAlign('right');
		$giveUp->setPosition(160.1, $this->gui->lobbyBoxPosY + 4.7);
		$giveUp->set(\ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this, 'onPlayerGiveUp'), true));
		$giveUp->show();

		switch($this->state)
		{
			case self::DECIDING:
				$ratios = array(
					array('Command' => 'nextMap', 'Ratio' => -1.),
					array('Command' => 'jumpToMapIndex', 'Ratio' => -1.),
				);
				$this->connection->setCallVoteRatios($ratios);
				$this->connection->chatSendServerMessage(static::PREFIX.' Match is starting.');
				break;
			case static::PLAYER_LEFT:
				$this->connection->chatSendServerMessage(static::PREFIX.'Player is back, match continues.');
				break;
			case static::WAITING_BACKUPS:
				$this->connection->chatSendServerMessage(static::PREFIX.'Substitute are connected, match continues.');
				break;
		}

		$this->changeState(self::PLAYING);
	}

	protected function over()
	{
		\ManiaLive\Utilities\Logger::debug('over()');
		Windows\GiveUp::EraseAll();
		$this->changeState(self::OVER);
	}

	protected function waitBackups()
	{
		$config = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance();
		if($config->waitingForBackups == 0)
		{
			$this->cancel();
			return;
		}

		if($this->match->team1 && $this->match->team2)
		{
			if(
				$this->countConnectedPlayers($this->match->team1) <= $config->minPlayersByTeam ||
				$this->countConnectedPlayers($this->match->team2) <= $config->minPlayersByTeam
			)
			{
				\ManiaLive\Utilities\Logger::debug('Not enough players. Match cancel');
				$this->cancel();
				return;
			}
		}

		\ManiaLive\Utilities\Logger::debug('waitBackups()');
		$this->changeState(self::WAITING_BACKUPS);
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::WAITING_BACKUPS);
		$this->waitingBackupTime = 0;
	}

	protected function updatePlayerList(Services\Match $match)
	{
		\ManiaLive\Utilities\Logger::debug('updatePlayerList()');
		$newPlayers = array_diff($match->players, $this->match->players);
		foreach($this->players as $login => $state)
		{
			if(in_array($state, array(Services\PlayerInfo::PLAYER_STATE_QUITTER, Services\PlayerInfo::PLAYER_STATE_GIVE_UP)))
			{
				unset($this->players[$login]);
				$this->connection->removeGuest($login);
			}
		}
		foreach($newPlayers as $player)
		{
			$this->connection->addGuest($player);
			$this->players[$player] = Services\PlayerInfo::PLAYER_STATE_NOT_CONNECTED;
		}
		$this->match = $match;
	}

	/**
	 * Free the match for the lobby
	 */
	protected function end()
	{
		\ManiaLive\Utilities\Logger::debug('end()');

		$jumper = Windows\ForceManialink::Create();
		$jumper->set('maniaplanet://#qjoin='.$this->lobby->backLink);
		$jumper->show();
		$this->connection->cleanGuestList();

		$this->match = null;
		$this->matchId = null;
		$this->matchMakingService->updateServerCurrentMatchId(
			null,
			$this->storage->serverLogin,
			$this->scriptName,
			$this->titleIdString
		);
//		$this->connection->setForcedTeams(false);
		$this->sleep();
	}

	protected function changeState($state)
	{
		if($this->intervals[$state])
		{
			if($this->state != static::PLAYER_LEFT || $this->state != $state)
			{
				$this->nextTick = new \DateTime($this->intervals[$state]);
			}
			$this->enableTickerEvent();
		}
		else $this->disableTickerEvent();

		$this->matchMakingService->registerMatchServer(
			$this->storage->serverLogin,
			$this->lobby->login,
			$this->state,
			$this->scriptName,
			$this->titleIdString
		);
		if ($this->state != $state)
		{
			\ManiaLive\Utilities\Logger::debug(sprintf('State: %d', $state));
		}

		$this->state = $state;
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

	protected function updateMatchPlayerState($login, $state)
	{
		$this->players[$login] = $state;
		$this->matchMakingService->updatePlayerState($login, $this->matchId, $state);
	}

	protected function registerRankings($rankings)
	{
		foreach($rankings as $ranking)
		{
			$this->matchMakingService->updatePlayerRank($ranking['Login'], $this->matchId, $ranking['Rank']);
		}
	}

	/**
	 * @param string[] $team
	 */
	protected function countConnectedPlayers(array $logins)
	{
		$count = 0;
		foreach($logins as $login)
		{
			if(array_key_exists($login, $this->players) && $this->players[$login] == Services\PlayerInfo::PLAYER_STATE_CONNECTED)
			{
				$count += 1;
			}
		}
		return $count;
	}
}

?>
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

	const PREFIX = '$000»$09f ';

	const TIME_WAITING_CONNECTION = 105;
	const TIME_WAITING_BACKUP = 20;

	/**
	 * @var int
	 */
	protected $tick = 0;

	/**
	 * @var int
	 */
	protected $lastRegisterTick = 0;

	/** @var int */
	protected $state = self::SLEEPING;

	/** @var \DateTime */
	protected $nextTick = null;

	/** @var string[] */
	protected $intervals = array(
		self::PLAYER_LEFT => '40 seconds',
		self::WAITING => '5 seconds',
		self::SLEEPING => '2 seconds',
		self::DECIDING => '50 seconds',
		self::PLAYING => '15 seconds',
		self::OVER => '10 seconds',
		self::WAITING_BACKUPS => '1 seconds'
	);

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

	protected $scores = array();

	/** @var \ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary */
	protected $dictionary;
	
	/**
	 * @var \DateTime 
	 */
	protected $ignoreEndMatchUntil;

	function onInit()
	{
		$this->setVersion('3.5.1');

		if (version_compare(\ManiaLiveApplication\Version, \ManiaLivePlugins\MatchMakingLobby\Config::REQUIRED_MANIALIVE) < 0)
		{
			throw new \ManiaLive\Application\FatalException(sprintf('You ManiaLive version is too old, please update to %s', \ManiaLivePlugins\MatchMakingLobby\Config::REQUIRED_MANIALIVE));
		}

		$this->config = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance();

		//Get the Script name
		$script = $this->connection->getScriptName();
		$this->scriptName = $this->config->script ? : preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script['CurrentValue']);
		$this->titleIdString = $this->connection->getSystemInfo()->titleId;

		//Get the GUI abstraction class
		$guiClassName = $this->config->getGuiClassName($this->scriptName);
		if (!class_exists($guiClassName))
		{
			throw new \Exception(sprintf("Can't find class %s. You should either set up the config : ManiaLivePlugins\MatchMakingLobby\Config.guiClassName or the script name",$guiClassName));
		}
		$this->setGui(new $guiClassName());

		//Load services
		$this->matchMakingService = new Services\MatchMakingService();
		$this->matchMakingService->createTables();

		$this->dictionary = \ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::getInstance($this->config->getDictionnary($this->scriptName));
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

		$ratios = array(
			new \DedicatedApi\Structures\VoteRatio('SetModeScriptSettingsAndCommands', -1.),
			new \DedicatedApi\Structures\VoteRatio('Kick', 0.7),
			new \DedicatedApi\Structures\VoteRatio('Ban', -1.),
			new \DedicatedApi\Structures\VoteRatio('AutoTeamBalance', -1.),
			new \DedicatedApi\Structures\VoteRatio('RestartMap', -1.)
		);

		$this->connection->setCallVoteRatiosEx(false, $ratios);
		$this->connection->setCallVoteTimeOut(15000);

		$this->state = self::SLEEPING;

		$this->enableTickerEvent();

		//Set needed rules to run the lobny
		$matchSettingsClass = $this->config->getMatchSettingsClassName($this->scriptName);
		/* @var $matchSettings \ManiaLivePlugins\MatchMakingLobby\MatchSettings\MatchSettings */
		if (!class_exists($matchSettingsClass))
		{
			throw new \Exception(sprintf("Can't find class %s. You should set up the config : ManiaLivePlugins\MatchMakingLobby\Config.matchSettingsClassName",$matchSettingsClass));
		}

		$matchSettings = new $matchSettingsClass();
		$settings = $matchSettings->getMatchScriptSettings();
		$this->connection->setModeScriptSettings($settings);

		$this->lobby = $this->matchMakingService->getLobby($this->config->lobbyLogin);
		if (!$this->lobby)
		{
			throw new \Exception('The lobby can\'t be found in the database. Are you sure that lobby and match server are sharing the same database?');
		}

		$this->connection->setServerTag('nl.lobbylogin', $this->config->lobbyLogin);
		
		$this->connection->customizeQuitDialog($this->gui->getCustomizedQuitDialogManiaLink(), '#qjoin='.$this->lobby->backLink, false, 10000);

		//Check if a match existed before to cancel it
		$match = $this->matchMakingService->getServerCurrentMatch($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		if ($match)
		{
			$this->matchMakingService->updateMatchState($match->id, Services\Match::FINISHED);
			$this->matchMakingService->updateServerCurrentMatchId(null, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		}

		//Restart map to initialize script
		$this->connection->executeMulticall(); // Flush calls
		$this->connection->restartMap();
	}

	function onUnload()
	{
		if($this->matchMakingService instanceof Services\MatchMakingService && $this->matchId)
		{
			$this->matchMakingService->updateMatchState($this->matchId, Services\Match::FINISHED);
			$this->end();
		}
		$this->connection->customizeQuitDialog('', '', true, 0);
		parent::onUnload();
	}

	//Core of the plugin
	function onTick()
	{
		$this->tick++;

		switch ($this->state)
		{
			case self::OVER:
			case self::SLEEPING:
				break;

			case self::WAITING:
			case self::DECIDING:
			case self::PLAYING:
				break;

			case self::PLAYER_LEFT:
			case self::WAITING_BACKUPS:
				break;
		}
		if(new \DateTime() < $this->nextTick) return;

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
					$this->cancel(false);
					break;
				}
				if($this->match->isDifferent($match))
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
				$this->changeState(static::PLAYING);
				break;
			case self::PLAYER_LEFT:
				\ManiaLive\Utilities\Logger::debug('tick: PLAYER_LEFT');
				$this->waitBackups();
				break;
			case self::WAITING_BACKUPS:
				switch($this->config->waitingForBackups)
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
					if($match && $this->match->isDifferent($match))
					{
						$this->updatePlayerList($match);
					}
					$this->changeState(self::WAITING_BACKUPS);
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
		//Force player as player
		$this->connection->forceSpectator($login, ($isSpectator ? 1 : 2));
		\ManiaLive\Utilities\Logger::debug('player '.$login.' connected');
		switch ($this->state)
		{
			case static::SLEEPING:
				\ManiaLive\Utilities\Logger::debug('ERROR: incoherent state: player connected while match sleeping');
				$this->showTansfertLabel($login);
				$this->connection->sendOpenLink($login, '#qjoin='.$this->lobby->backLink, 1);
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
		{
			try
			{
				$this->forcePlayerTeam($playerInfo['Login']);
			}
			catch (\DedicatedApi\Xmlrpc\Exception $e)
			{
				//do thing
			}
		}
	}
	
	function onPlayerDisconnect($login, $disconnectionReason)
	{
		\ManiaLive\Utilities\Logger::debug(sprintf('Player disconnected: %s (%s)', $login, $disconnectionReason));
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
				if($disconnectionReason != '')
				{
					$this->giveUp($login);
					return;
				}
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
		if (new \DateTime() < $this->ignoreEndMatchUntil)
		{
			\ManiaLive\Utilities\Logger::debug('onEndMach ignored');
			return;
		}
		\ManiaLive\Utilities\Logger::debug('onEndMach:'.$this->connection->checkEndMatchCondition());
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
				$this->connection->triggerModeScriptEvent('LibXmlRpc_GetScores', '');
				$this->matchMakingService->updateMatchState($this->matchId, Services\Match::FINISHED);
				$this->over();
				break;
			case static::OVER:
				break;
		}
	}

	function onModeScriptCallback($param1, $param2)
	{
		switch($param1)
		{
			case 'Master':
				$login = $param2[0];
				$player = $this->storage->getPlayerObject($login);
				if ($player !== null)
				{
					$service = new Services\MatchMakingService();
					$service->addMaster($login, $player->nickName, $player->ladderStats['PlayerRankings'][0]['Score'], $this->lobby->login, $this->scriptName, $this->titleIdString);
				}
				break;
			case 'MatchmakingGetOrder':
				if($this->match)
				{
					$allyService = Services\AllyService::getInstance($this->lobby->login, $this->scriptName, $this->titleIdString);

					$alliesList = array();
					foreach($this->match->players as $login)
					{
						$alliesList = array_merge($alliesList, $allyService->get($login));
					}
					$alliesList = array_unique($alliesList);
					$this->connection->triggerModeScriptEvent('MatchmakingSetTempAllies', implode(',', $alliesList));
				}
				break;
			case 'LibXmlRpc_Scores':
				if($this->matchId)
				{
					$this->scores['match'][0] = $param2[0];
					$this->scores['match'][1] = $param2[1];
					$this->scores['map'][0] = $param2[2];
					$this->scores['map'][1] = $param2[3];
					$this->matchMakingService->updateMatchScores($this->matchId, $this->scores['match'][0], $this->scores['match'][1], $this->scores['map'][0], $this->scores['map'][1]);
				}
				break;
			case 'LibXmlRpc_EndRound':
				$this->connection->triggerModeScriptEvent('LibXmlRpc_GetScores', '');
				break;
			case 'LibXmlRpc_EndTurn':
				$this->connection->triggerModeScriptEvent('LibXmlRpc_GetScores', '');
				break;
			case 'LibXmlRpc_EndMap':
				$this->connection->triggerModeScriptEvent('LibXmlRpc_GetScores', '');
				break;
			case 'LibXmlRpc_EndMatch':
				$this->connection->triggerModeScriptEvent('LibXmlRpc_GetScores', '');
				break;
		}
	}

	function onPlayerGiveUp($login)
	{
		$this->giveUp($login);
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
		Label::EraseAll();

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
			ServerEvent::ON_END_ROUND |
			ServerEvent::ON_PLAYER_INFO_CHANGED |
			ServerEvent::ON_MODE_SCRIPT_CALLBACK |
			ServerEvent::ON_MODE_SCRIPT_CALLBACK_ARRAY
		);

		\ManiaLive\Utilities\Logger::debug(sprintf('Preparing match for %s (%s)',$this->lobby->login, implode(',', array_keys($this->players))));
		$this->changeState(self::WAITING);
		$this->waitingTime = 0;
		$this->connection->setForcedTeams(true);
	}

	protected function sleep()
	{
		$this->changeState(self::SLEEPING);
	}

	protected function playerIllegalLeave($login)
	{
		\ManiaLive\Utilities\Logger::debug('Player illegal leave: '.$login);

		$this->gui->createLabel($this->gui->getIllegalLeaveText(), null, null, false, false);
		
		$this->updateMatchPlayerState($login, Services\PlayerInfo::PLAYER_STATE_QUITTER);


		//If already waiting backup, no need to change state.
		//maybe the player won't have his 40 seconds to come back
		//but hard to manage all possible cases
		if ($this->state == self::WAITING_BACKUPS)
		{
			//Forcing wait backup to handle match cancellation etc...
			$this->waitBackups();
		}
		else
		{
			$this->changeState(self::PLAYER_LEFT);
		}
	}

	protected function giveUp($login)
	{
		\ManiaLive\Utilities\Logger::debug('Player '.$login.' gave up.');

		$this->updateMatchPlayerState($login, Services\PlayerInfo::PLAYER_STATE_GIVE_UP);

		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::WAITING_BACKUPS);

		$this->gui->createLabel($this->gui->getGiveUpText(), null, null, false, false);

		$this->waitBackups();
	}

	protected function cancel($updateState = true)
	{
		\ManiaLive\Utilities\Logger::debug('cancel()');

		if ($updateState)
		{
			$this->matchMakingService->updateMatchState($this->matchId, Services\Match::PLAYER_LEFT);
		}

		$this->gui->createLabel($this->gui->getMatchoverText(), null, null, false, false);

		$this->connection->chatSendServerMessageToLanguage($this->dictionary->getChat(array(
			array('textId' => 'matchAborted', 'params' => array(static::PREFIX))
		)));

		$this->over();
	}

	protected function decide()
	{
		\ManiaLive\Utilities\Logger::debug('decide()');
		if($this->state != self::DECIDING)
		{
			$this->connection->chatSendServerMessageToLanguage($this->dictionary->getChat(array(
					array('textId' => 'matchDeciding', 'params' => array(static::PREFIX))
			)));
			$ratios = array();
			$ratios[] = new \DedicatedApi\Structures\VoteRatio('NextMap', 0.5);
			$ratios[] = new \DedicatedApi\Structures\VoteRatio('JumpToMapIndex', 0.5);
			$this->connection->setCallVoteRatiosEx(false, $ratios);
			
			$this->ignoreEndMatchUntil = new \DateTime('+ 3 minutes');
		}

		$this->changeState(self::DECIDING);
	}

	protected function play()
	{
		\ManiaLive\Utilities\Logger::debug('play()');
		$this->matchMakingService->updateMatchState($this->matchId, Services\Match::PLAYING);

		Label::EraseAll();

		switch($this->state)
		{
			case self::DECIDING:
				$ratios = array();
				$ratios[] = new \DedicatedApi\Structures\VoteRatio('NextMap', -1.);
				$ratios[] = new \DedicatedApi\Structures\VoteRatio('JumpToMapIndex', -1.);
				$this->connection->setCallVoteRatiosEx(false, $ratios);

				$this->connection->chatSendServerMessageToLanguage($this->dictionary->getChat(array(
						array('textId' => 'matchStarting', 'params' => array(static::PREFIX))
				)));
				break;
			case static::PLAYER_LEFT:
				$this->connection->chatSendServerMessageToLanguage(array(
					array('Lang' => 'fr', 'Text' => static::PREFIX.'Le joueur est de retour.'),
					array('Lang' => 'en', 'Text' => static::PREFIX.'Player is back.'),
				));
				break;
			case static::WAITING_BACKUPS:
				$this->connection->chatSendServerMessageToLanguage(array(
					array('Lang' => 'fr', 'Text' => static::PREFIX.'Les remplaçants sont connectés.'),
					array('Lang' => 'en', 'Text' => static::PREFIX.'Substitutes are connected.'),
				));
				break;
		}

		$this->changeState(self::PLAYING);
	}

	protected function over()
	{
		\ManiaLive\Utilities\Logger::debug('over()');
		$this->changeState(self::OVER);
	}

	protected function waitBackups()
	{
		if($this->config->waitingForBackups == 0)
		{
			$this->cancel();
			return;
		}

		if ($this->countConnectedPlayers(array_keys($this->players)) <= 1)
		{
			$this->cancel();
			return;
		}

		if($this->match->team1 && $this->match->team2)
		{
			if($this->countConnectedPlayers($this->match->team1) <= $this->config->minPlayersByTeam ||
				$this->countConnectedPlayers($this->match->team2) <= $this->config->minPlayersByTeam)
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
				$this->connection->removeGuest((string) $login, true);
			}
		}
		foreach($newPlayers as $player)
		{
			$this->connection->addGuest((string) $player, true);
			$this->players[$player] = Services\PlayerInfo::PLAYER_STATE_NOT_CONNECTED;
		}
		$this->connection->executeMulticall();
		$this->match = $match;
	}

	/**
	 * Free the match for the lobby
	 */
	protected function end()
	{
		\ManiaLive\Utilities\Logger::debug('end()');

		$this->showTansfertLabel(null, -50);
		foreach($this->storage->players as $player)
		{
			try
			{
				$this->connection->sendOpenLink((string) $player->login, '#qjoin='.$this->lobby->backLink, 1);
			}
			catch (\DedicatedApi\Xmlrpc\Exception $e)
			{
				//do nothing
			}
		}
		$this->connection->cleanGuestList();

		$this->match = null;
		$this->matchId = null;
		$this->matchMakingService->updateServerCurrentMatchId(
			null,
			$this->storage->serverLogin,
			$this->scriptName,
			$this->titleIdString
		);
		$this->connection->setForcedTeams(false);
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

		if (($this->state != self::SLEEPING || (($this->tick - $this->lastRegisterTick) > 12)))
		{
			$this->matchMakingService->registerMatchServer(
				$this->storage->serverLogin,
				$this->lobby->login,
				$this->state,
				$this->scriptName,
				$this->titleIdString,
				$this->storage->currentMap->name
			);

			$this->lastRegisterTick = $this->tick;
		}
		if ($this->state != $state)
		{
			\ManiaLive\Utilities\Logger::debug(sprintf('State: %d', $state));
		}

		$this->state = $state;
	}

	protected function isEverybodyHere()
	{
		$matchMakerClassName = $this->config->getMatchMakerClassName($this->scriptName);
		$matchMaker = $matchMakerClassName::getInstance();
		return count(array_filter($this->players, function ($p) { return $p == Services\PlayerInfo::PLAYER_STATE_CONNECTED; })) == $matchMaker->getPlayersPerMatch();
	}

	protected function setGui(GUI\AbstractGUI $GUI)
	{
		$this->gui = $GUI;
	}

	protected function updateMatchPlayerState($login, $state)
	{
		if ($state == Services\PlayerInfo::PLAYER_STATE_QUITTER || $state == Services\PlayerInfo::PLAYER_STATE_GIVE_UP)
		{
			$this->matchMakingService->increasePlayerPenalty(
					$login,
					$this->config->penaltyForQuitter,
					$this->lobby->login,
					$this->scriptName,
					$this->titleIdString
			);
		}
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

	protected function showTansfertLabel($login = null, $posY = 0)
	{
		$label = Label::Create($login);
		$label->hideOnF6 = false;
		$label->showBackground = true;
		$label->setMessage('transfer');
		$label->setPosY($posY);
		$label->show();
	}
}

?>

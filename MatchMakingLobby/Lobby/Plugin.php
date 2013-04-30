<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Lobby;

use DedicatedApi\Structures;
use ManiaLive\DedicatedApi\Callback\Event as ServerEvent;
use ManiaLive\Gui\Windows\Shortkey;
use ManiaLivePlugins\MatchMakingLobby\Windows;
use ManiaLivePlugins\MatchMakingLobby\Services;
use ManiaLivePlugins\MatchMakingLobby\Config;
use ManiaLivePlugins\MatchMakingLobby\GUI;
use ManiaLivePlugins\MatchMakingLobby\Services\Match;

class Plugin extends \ManiaLive\PluginHandler\Plugin
{

	const PREFIX = 'LobbyInfo$000»$8f0 ';

	/** @var int */
	protected $tick;

	/** @var int */
	protected $mapTick;

	/** @var Config */
	protected $config;

	/** @var MatchMakers\MatchMakerInterface */
	protected $matchMaker;

	/** @var GUI\AbstractGUI */
	protected $gui;

	/** @var string */
	protected $backLink;

	/** @var int[string] */
	protected $countDown = array();

	/** @var int[string] */
	protected $replacerCountDown = array();

	/** @var string[string]  */
	protected $replacers = array();

	/** @var int[string] */
	protected $blockedPlayers = array();

	/** @var Services\MatchMakingService */
	protected $matchMakingService;

	/** @var string */
	protected $scriptName;

	/** @var string */
	protected $titleIdString;

	/** @var bool */
	protected $updatePlayerList = false;
	
	function onInit()
	{
		$this->setVersion('2.0.0');

		if (version_compare(\ManiaLiveApplication\Version, \ManiaLivePlugins\MatchMakingLobby\Config::REQUIRED_MANIALIVE) < 0)
		{
			throw new \ManiaLive\Application\FatalException(sprintf('You ManiaLive version is too old, please update to %s', \ManiaLivePlugins\MatchMakingLobby\Config::REQUIRED_MANIALIVE));
		}

		//Load MatchMaker and helpers for GUI
		$this->config = Config::getInstance();
		$script = $this->storage->gameInfos->scriptName;
		$this->scriptName = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance()->script ? : preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script);

		$matchMakerClassName = $this->config->matchMakerClassName ? : __NAMESPACE__.'\MatchMakers\\'.$this->scriptName;
		if (!class_exists($matchMakerClassName))
		{
			throw new \Exception(sprintf("Can't find class %s. You should either set up the config : ManiaLivePlugins\MatchMakingLobby\Config.matchMakerClassName or the script name",$matchMakerClassName));
		}
		$guiClassName = $this->config->guiClassName ? : '\ManiaLivePlugins\MatchMakingLobby\GUI\\'.$this->scriptName;
		if (!class_exists($guiClassName))
		{
			throw new \Exception(sprintf("Can't find class %s. You should either set up the config : ManiaLivePlugins\MatchMakingLobby\Config.guiClassName or the script name",$guiClassName));
		}

		$this->setGui(new $guiClassName());
		$this->gui->lobbyBoxPosY = 45;
		$this->setMatchMaker($matchMakerClassName::getInstance());
	}

	function onLoad()
	{
		//Check if Lobby is not running with the match plugin
		if($this->isPluginLoaded('MatchMakingLobby/Match'))
		{
			throw new \Exception('Lobby and match cannot be one the same server.');
		}
		$this->enableDedicatedEvents(
			ServerEvent::ON_PLAYER_CONNECT |
			ServerEvent::ON_PLAYER_DISCONNECT |
			ServerEvent::ON_PLAYER_ALLIES_CHANGED |
			ServerEvent::ON_BEGIN_MAP |
			ServerEvent::ON_PLAYER_INFO_CHANGED
		);

		//FIXME: move to $this->onInit()
		$matchSettingsClass = $this->config->matchSettingsClassName ? : '\ManiaLivePlugins\MatchMakingLobby\MatchSettings\\'.$this->scriptName;
		/* @var $matchSettings \ManiaLivePlugins\MatchMakingLobby\MatchSettings\MatchSettings */
		if (!class_exists($matchSettingsClass))
		{
			throw new \Exception(sprintf("Can't find class %s. You should set up the config : ManiaLivePlugins\MatchMakingLobby\Config.matchSettingsClassName",$matchSettingsClass));
		}

		$matchSettings = new $matchSettingsClass();
		$settings = $matchSettings->getLobbyScriptSettings();
		$this->connection->setModeScriptSettings($settings);
		$this->connection->restartMap();

		$this->enableTickerEvent();

		$this->matchMakingService = new Services\MatchMakingService();
		$this->matchMakingService->createTables();

		$this->titleIdString = $this->connection->getSystemInfo()->titleId;

		$this->backLink = $this->storage->serverLogin.':'.$this->storage->server->password.'@'.$this->titleIdString;

		$this->setLobbyInfo();
		foreach(array_merge($this->storage->players, $this->storage->spectators) as $login => $obj)
		{
			$playerObject =  $this->storage->getPlayerObject($login);
			$player = Services\PlayerInfo::Get($login);
			$player->ladderPoints = $playerObject->ladderStats['PlayerRankings'][0]['Score'];
			$player->allies = $playerObject->allies;
			$this->gui->createPlayerList($login);
			$this->setShortKey($login, array($this,'onPlayerReady'));
			$this->gui->createLabel($this->gui->getNotReadyText(), $login);

			$help = Windows\Help::Create($login);
			$help->modeName = $this->scriptName;
			$help->displayHelp = ($playerObject->isSpectator ? true : false);
			$help->show();
		}
		$this->updatePlayerList = true;

		$this->registerLobby();

		$voteRatio = new Structures\VoteRatio();
		$voteRatio->command = 'SetModeScriptSettings';
		$voteRatio->ratio = -1.;
		$this->connection->setCallVoteRatiosEx(false, array($voteRatio));

		$playersCount = $this->getReadyPlayersCount();
		$totalPlayerCount = $this->getTotalPlayerCount();

		$this->gui->updateLobbyWindow(
			$this->storage->server->name, 
			$playersCount, 
			$totalPlayerCount, 
			$this->getPlayingPlayersCount(), 
			$this->matchMakingService->getAverageTimeBetweenMatches($this->storage->serverLogin, $this->scriptName, $this->titleIdString)
		);
	}

	function onUnload()
	{
		$this->setLobbyInfo(false);
		parent::onUnload();
	}

	function onPlayerConnect($login, $isSpectator)
	{
		\ManiaLive\Utilities\Logger::debug(sprintf('Player connected: %s', $login));
		$match = $this->matchMakingService->getPlayerCurrentMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		if($match)
		{
			\ManiaLive\Utilities\Logger::debug(sprintf('send %s to is match %d', $login, $match->id));
			$jumper = Windows\ForceManialink::Create($login);
			$jumper->set('maniaplanet://#qjoin='.$match->matchServerLogin.'@'.$match->titleIdString);
			$jumper->show();
			$this->gui->createLabel($this->gui->getMatchInProgressText(), $login);
			return;
		}
		$playerObject = $this->storage->getPlayerObject($login);
		if(!$playerObject)
		{
			return;
		}

		$message = '';
		$player = Services\PlayerInfo::Get($login);
		$player->isInMatch = $this->matchMakingService->isInMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		$message = ($player->ladderPoints ? $this->gui->getPlayerBackLabelPrefix() : '').$this->gui->getNotReadyText();
		$player->setAway(false);
		$player->ladderPoints = $playerObject->ladderStats['PlayerRankings'][0]['Score'];
		$player->allies = $playerObject->allies;

		$this->gui->createLabel($message, $login);

		$this->setShortKey($login, array($this, 'onPlayerReady'));

		$this->gui->createPlayerList($login);
		$this->updatePlayerList = true;

		$this->updateKarma($login);

		$help = Windows\Help::Create($login);
		$help->modeName = $this->scriptName;
		$help->show();

		$this->checkAllies($player);

		try
		{
			$this->connection->removeGuest($login);
		}
		catch(\DedicatedApi\Xmlrpc\Exception $e)
		{

		}
	}

	function onPlayerDisconnect($login, $disconnectionReason)
	{
		\ManiaLive\Utilities\Logger::debug(sprintf('Player disconnected: %s', $login));

		$player = Services\PlayerInfo::Get($login);
		$player->setAway();

		if(array_key_exists($login, $this->blockedPlayers))
		{
			unset($this->blockedPlayers[$login]);
		}

		$match = $this->matchMakingService->getPlayerCurrentMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		if($match)
		{
			if (array_key_exists($match->id, $this->countDown) && $this->countDown[$match->id] > 0)
			{
				$this->onPlayerCancelMatchStart($login);
			}
			if (array_key_exists($login, $this->replacerCountDown) && $this->replacerCountDown[$login] > 0)
			{
				$this->onPlayerCancelReplacement($login);
			}
		}
		$this->gui->removePlayerFromPlayerList($login);
	}

	function onPlayerInfoChanged($playerInfo)
	{
		$playerInfo = $this->storage->getPlayerObject($playerInfo['Login']);
		if(!$playerInfo)
		{
			return;
		}
		if($playerInfo->spectator)
		{
			$help = Windows\Help::Create($playerInfo->login);
			$help->displayHelp = true;
			$help->modeName = $this->scriptName;
			$help->redraw();
		}
		$player = Services\PlayerInfo::Get($playerInfo->login);
		if(!$player->isReady())
		{
			$player->setReady(false);
		}

		/*if($playerInfo->hasJoinedGame)
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
		}*/
	}

	function onBeginMap($map, $warmUp, $matchContinuation)
	{
		$this->mapTick = 0;
		$this->connection->restartMap();
	}

	//Core of the plugin
	function onTick()
	{
		$timers = array();
		$mtime = microtime(true);
		foreach($this->blockedPlayers as $login => $time)
		{
			$this->updateKarma($login);
		}
		$timers['blocked'] = microtime(true) - $mtime;

		//If there is some match needing players
		//find backup in ready players and send them to the match server
		$mtime = microtime(true);
		$matchesNeedingBackup = $this->matchMakingService->getMatchesNeedingBackup($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		if ($matchesNeedingBackup)
		{
			$potentialBackups = $this->getMatchablePlayers();
			$storage = $this->storage;
			$potentialBackups = array_filter($potentialBackups, function ($login) use ($storage)
			{
				$obj = $storage->getPlayerObject($login);
				if($obj)
				{
					return !count($obj->allies);
				}
			});
			foreach($matchesNeedingBackup as $match)
			{
				$potentialBackupsForMatch = array_filter($potentialBackups,
					function ($backup) use ($match)
					{
						return !in_array($backup, $match->players);
					}
				);

				/** @var Match $match */
				$quitters = $this->matchMakingService->getMatchQuitters($match->id);
				foreach ($quitters as $quitter)
				{
					$backup = $this->matchMaker->getBackup($quitter, $potentialBackupsForMatch);
					if ($backup)
					{
						\ManiaLive\Utilities\Logger::debug(
						sprintf('match %d, %s will replace %s', $match->id, $backup, $quitter)
						);

						$this->matchMakingService->updatePlayerState($quitter, $match->id, Services\PlayerInfo::PLAYER_STATE_REPLACER_PROPOSED);
						$this->replacers[$backup] = $quitter;

						$teamId = $match->getTeam($quitter);
						$this->matchMakingService->addMatchPlayer($match->id, $backup, $teamId);
						$this->gui->createLabel($this->gui->getBackUpLaunchText(), $backup, 0, false, false);
						$this->setShortKey($backup, array($this, 'onPlayerCancelReplacement'));
						$this->gui->prepareJump(array($backup), $match->matchServerLogin, $match->titleIdString, $backup);
						$this->replacerCountDown[$backup] = 7;

						//Unset this replacer for next iteration
						unset($potentialBackupsForMatch[array_search($backup, $potentialBackupsForMatch)]);
						unset($potentialBackups[array_search($backup, $potentialBackups)]);
					}
				}
			}
		}
		$timers['backups'] = microtime(true) - $mtime;

		if(++$this->tick % 16 == 0)
		{
			//Check if a server is available
			if ($this->matchMakingService->countAvailableServer($this->storage->serverLogin, $this->scriptName, $this->titleIdString) > 0)
			{
				$mtime = microtime(true);
				$matches = $this->matchMaker->run($this->getMatchablePlayers());
				foreach($matches as $match)
				{
					/** @var Match $match */
					$server = $this->matchMakingService->getAvailableServer(
						$this->storage->serverLogin,
						$this->scriptName,
						$this->titleIdString
					);
					if($server)
					{
						//Match ready, let's prepare it !
						$this->prepareMatch($server, $match);
					}
					else
					{
						//FIXME: we shouldn't be in this case.. remove ?
						foreach($match->players as $login)
						{
							$this->gui->createLabel($this->gui->getNoServerAvailableText(), $login);
						}
					}
				}
				$timers['match'] = microtime(true) - $mtime;
			}
			// No server available for this match
			else
			{
				$readyPlayers = Services\PlayerInfo::GetReady();
				foreach ($readyPlayers as $readyPlayer)
				{
					$this->gui->createLabel($this->gui->getNoServerAvailableText(), $readyPlayer->login);
				}
			}
		}

		foreach($this->replacerCountDown as $login => $countDown)
		{
			switch(--$countDown)
			{
				case -15:
					$this->gui->eraseJump($login);
					unset($this->replacerCountDown[$login]);
					break;
				case 0:
					$match = $this->matchMakingService->getPlayerCurrentMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
					$player = $this->storage->getPlayerObject($login);
					if($match->state >= Match::PREPARED)
					{
						$this->matchMakingService->updatePlayerState($this->replacerCountDown[$login], $match->id, Services\PlayerInfo::PLAYER_STATE_REPLACED);
						$this->gui->showJump($login);
						$this->connection->addGuest($login, true);
						$this->connection->chatSendServerMessage(self::PREFIX.$player->nickName.' joined a match as a substitute.', null);
					}
					unset($match, $player);
					//nobreak

				default:
					$this->replacerCountDown[$login] = $countDown;
					break;
			}
		}
		unset($login, $countDown);

		$mtime = microtime(true);
		foreach($this->countDown as $matchId => $countDown)
		{
			switch(--$countDown)
			{
				case -15:
					$this->gui->eraseJump($matchId);
					unset($this->countDown[$matchId]);
					break;
				case 0:
					\ManiaLive\Utilities\Logger::debug(sprintf('prepare jump for match : %d', $matchId));
					$match = $this->matchMakingService->getMatch($matchId);
					if($match->state >= Match::PREPARED)
					{
						\ManiaLive\Utilities\Logger::debug(sprintf('jumping to server : %s', $match->matchServerLogin));
						$players = array_map(array($this->storage, 'getPlayerObject'), $match->players);
						$this->gui->showJump($matchId);

						$nicknames = array();
						foreach($players as $player)
						{
							if($player && !array_key_exists($player->login, $this->blockedPlayers))
							{
								$nicknames[] = '$<'.$player->nickName.'$>';
								$this->connection->addGuest($player, true);
							}
						}
						$this->connection->chatSendServerMessage(self::PREFIX.implode(' & ', $nicknames).' join a match.', null);
					}
					else
					{
						\ManiaLive\Utilities\Logger::debug(sprintf('jump cancel match state is : %d', $match->state));
						foreach($match->players as $player)
						{
							$this->gui->createLabel($this->gui->getReadyText(), $player);
						}
					}
					unset($match);
					//nobreak
				default:
					$this->countDown[$matchId] = $countDown;
					break;
			}
		}
		$timers['jumper'] = microtime(true) - $mtime;

		//Do periodic nextmap
		if(++$this->mapTick % 1800 == 0)
		{
			$this->connection->nextMap();
		}

		//Clean guest list for not in match players
		if($this->tick % 29 == 0)
		{
			$mtime = microtime(true);
			$guests = $this->connection->getGuestList(-1, 0);
			if(count($guests))
			{
				$guests = Structures\Player::getPropertyFromArray($guests,'login');
				$logins = $this->matchMakingService->getPlayersJustFinishedAllMatches($this->storage->serverLogin, $guests);
				if($logins)
				{
					foreach($logins as $login)
					{
						$this->connection->removeGuest($login, true);
					}
				}
			}
			$timers['cleanGuest'] = microtime(true) - $mtime;
		}

		if($this->tick % 31 == 0)
		{
			$mtime = microtime(true);
			$this->setLobbyInfo();
			$timers['lobbyInfo'] = microtime(true) - $mtime;
		}
		if($this->tick % 3 == 0)
		{
			$mtime = microtime(true);
			$this->updateLobbyWindow();
			$timers['lobbyWindow'] = microtime(true) - $mtime;
		}

		//Moving players that are not ready for a long time
		if($this->tick % 42 == 0)
		{
//			$notReadyPlayers = Services\PlayerInfo::GetNotReady();
//			foreach($notReadyPlayers as $notReadyPlayer)
//			{
//				if($notReadyPlayer->getNotReadyTime() > 240)
//				{
//					$this->connection->forceSpectator($notReadyPlayer->login, 3, true);
//				}
//			}
		}

		if($this->updatePlayerList)
		{
			$this->gui->updatePlayerList($this->blockedPlayers);
			$this->updatePlayerList = false;
		}
		$this->registerLobby();
		Services\PlayerInfo::CleanUp();

		$this->connection->executeMulticall();

		//Debug
		$timers = array_filter($timers, function($v) { return $v > 0.010; });
		if(count($timers))
		{
			$line = array();
			foreach($timers as $key => $value)
			{
				$line[] = sprintf('%s:%f',$key,$value);
			}
			\ManiaLive\Utilities\Logger::debug(implode('|', $line));
		}
	}

	function onPlayerReady($login)
	{
		$mtime = microtime(true);
		$player = Services\PlayerInfo::Get($login);
		if (!$this->matchMakingService->isInMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString))
		{
			$player->setReady(true);
			$this->setShortKey($login, array($this, 'onPlayerNotReady'));

			$this->setReadyLabel();

			$this->updatePlayerList = true;
		}
		else
		{
			\ManiaLive\Utilities\Logger::debug(sprintf('Player try to be ready while in match: %s', $login));
		}
		$time = microtime(true) - $mtime;
		if($time > 0.05)
			\ManiaLive\Utilities\Logger::debug(sprintf('onPlayerReady:%f',$time));
	}

	function onPlayerNotReady($login)
	{
		$mtime = microtime(true);
		$player = Services\PlayerInfo::Get($login);
		$player->setReady(false);
		$this->setShortKey($login, array($this, 'onPlayerReady'));
		$this->gui->createLabel($this->gui->getNotReadyText(), $login);

		$this->setReadyLabel();

		$this->updatePlayerList = true;

		$time = microtime(true) - $mtime;
		if($time > 0.05)
			\ManiaLive\Utilities\Logger::debug(sprintf('onPlayerNotReady:%f',$time));
	}

	function onPlayerAlliesChanged($login)
	{
		$player = $this->storage->getPlayerObject($login);
		if($player)
		{
			Services\PlayerInfo::Get($login)->allies = $player->allies;
			$this->checkAllies($player);
		}
		$this->updatePlayerList = true;
	}

	protected function checkAllies($player)
	{
		if ($this->matchMaker->getNumberOfTeam() > 0)
		{
			$alliesMax = ($this->matchMaker->getPlayersPerMatch()/$this->matchMaker->getNumberOfTeam())-1;
			//Too many allies
			if (count($player->allies) > $alliesMax)
			{
				$tooManyAlly = Windows\TooManyAllies::Create($player->login);
				$tooManyAlly->setPosition(0,60);
				$tooManyAlly->setText($this->gui->getTooManyAlliesText($alliesMax));
				$tooManyAlly->show();
			}
			else
			{
				Windows\TooManyAllies::Erase($player->login);
			}
		}
	}

	function onNothing()
	{
		//Do nothing
	}

	function doNotShow($login)
	{
		//TODO store data
		$this->gui->hideSplash($login);
	}

	function onPlayerCancelReplacement($login)
	{
		\ManiaLive\Utilities\Logger::debug('Player cancel replacement: '.$login);

		$player = $this->storage->getPlayerObject($login);

		$match = $this->matchMakingService->getPlayerCurrentMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);

		if ($match)
		{
			$this->gui->eraseJump($login);
			$this->matchMakingService->updatePlayerState($login, $match->id, Services\PlayerInfo::PLAYER_STATE_CANCEL);
			$this->matchMakingService->updatePlayerState($this->replacers[$login], $match->id, Services\PlayerInfo::PLAYER_STATE_QUITTER);

			$this->onPlayerReady($login);

			unset($this->replacerCountDown[$login]);
			unset($this->replacers[$login]);
		}
	}

	function onPlayerCancelMatchStart($login)
	{
		\ManiaLive\Utilities\Logger::debug('Player cancel match start: '.$login);

		$player = $this->storage->getPlayerObject($login);

		$match = $this->matchMakingService->getPlayerCurrentMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		if ($match !== false && $match->state == Match::PREPARED)
		{
			$this->gui->eraseJump($match->id);
			unset($this->countDown[$match->id]);

			$this->matchMakingService->cancelMatch($match);

			$this->matchMakingService->updatePlayerState($login, $match->id, Services\PlayerInfo::PLAYER_STATE_CANCEL);

			$this->connection->chatSendServerMessage(sprintf(static::PREFIX.'$<%s$> cancelled match start.', $player->nickName));

			foreach($match->players as $playerLogin)
			{
				Services\PlayerInfo::Get($playerLogin)->isInMatch = false;
				if($playerLogin != $login)
					$this->onPlayerReady($playerLogin);
				else
					$this->onPlayerNotReady($playerLogin);
			}
			$this->updateKarma($login);
		}
		else
		{
			if($match === false)
			{
				\ManiaLive\Utilities\Logger::debug(sprintf('error: player %s cancel unknown match start',$login, $match->id));
			}
			else
			{
				\ManiaLive\Utilities\Logger::debug(sprintf('error: player %s cancel match start (%d) not in prepared mode',$login, $match->id));
			}
		}
	}

	private function prepareMatch($server, $match)
	{

		$id = $this->matchMakingService->registerMatch($server, $match, $this->scriptName, $this->titleIdString, $this->storage->serverLogin);
		\ManiaLive\Utilities\Logger::debug(sprintf('Preparing match %d on server: %s',$id, $server));
		\ManiaLive\Utilities\Logger::debug($match);

		$this->gui->prepareJump($match->players, $server, $this->titleIdString, $id);
		$this->countDown[$id] = 11;

		foreach($match->players as $player)
		{
			$this->gui->createLabel($this->gui->getLaunchMatchText($match, $player), $player, $this->countDown[$id] - 1);
			$this->setShortKey($player, array($this, 'onPlayerCancelMatchStart'));
			Services\PlayerInfo::Get($player)->isInMatch = true;
		}

		$matchablePlayers = $this->getMatchablePlayers();
		if(count($matchablePlayers) < $this->matchMaker->getPlayersPerMatch())
		{
			$message = $this->gui->getNeedReadyPlayersText();
		}
		else
		{
			$message = $this->gui->getReadyText();
		}

		foreach($matchablePlayers as $login)
		{
			$this->gui->createLabel($message, $login);
		}

		$this->updatePlayerList = true;
	}

	private function getReadyPlayersCount()
	{
		$count = 0;
		foreach(array_merge($this->storage->players, $this->storage->spectators) as $player)
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
		return $this->matchMakingService->getPlayersPlayingCount($this->storage->serverLogin);
	}

	private function getTotalSlots()
	{
		$matchServerCount = $this->matchMakingService->getLiveMatchServersCount($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		return $matchServerCount * $this->matchMaker->getPlayersPerMatch() + $this->storage->server->currentMaxPlayers + $this->storage->server->currentMaxSpectators;
	}

	private function registerLobby()
	{
		$connectedPlayerCount = count($this->storage->players) + count($this->storage->spectators);
		$this->matchMakingService->registerLobby($this->storage->serverLogin, $this->getReadyPlayersCount(), $connectedPlayerCount, $this->storage->server->name, $this->backLink);
	}

	private function getLeavesCount($login)
	{
		return $this->matchMakingService->getLeaveCount($login, $this->storage->serverLogin);
	}

	/**
	 * @param $login
	 */
	private function updateKarma($login)
	{
		$player = $this->storage->getPlayerObject($login);
		$playerInfo = Services\PlayerInfo::Get($login);
		if ($player && $playerInfo)
		{
			$penalty = $this->matchMakingService->getPlayerPenalty($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
			if($penalty > 0)
			{
				if(array_key_exists($login, $this->blockedPlayers))
				{
					$this->matchMakingService->decreasePlayerPenalty($login, time() - $this->blockedPlayers[$login], $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
				}
				else
				{
					$this->connection->chatSendServerMessage(
						sprintf(self::PREFIX.'$<%s$> is suspended.', $player->nickName, $penalty)
					);
				}

				$this->blockedPlayers[$login] = time();

				$this->onPlayerNotReady($login);

				$this->gui->createLabel($this->gui->getBadKarmaText($penalty), $login, null, false, false);
				$this->resetShortKey($login);
				$this->updatePlayerList = true;
			}
			else
			{
				unset($this->blockedPlayers[$login]);
				$this->onPlayerNotReady($login);
			}
		}
		else
		{
			if(array_key_exists($login, $this->blockedPlayers))
			{
				unset($this->blockedPlayers[$login]);
			}
			\ManiaLive\Utilities\Logger::debug(sprintf('UpdateKarma for not connected player %s', $login));
		}
	}

	protected function setShortKey($login, $callback)
	{
		$shortKey = Shortkey::Create($login);
		$shortKey->removeCallback($this->gui->actionKey);
		$shortKey->addCallback($this->gui->actionKey, $callback);
	}

	protected function resetShortKey($login)
	{
		$shortKey = Shortkey::Create($login);
		$shortKey->removeCallback($this->gui->actionKey);
	}

	private function updateLobbyWindow()
	{
		$playersCount = $this->getReadyPlayersCount();
		$totalPlayerCount = $this->getTotalPlayerCount();
		$playingPlayersCount = $this->getPlayingPlayersCount();
		$this->gui->updateLobbyWindow(
			$this->storage->server->name, 
			$playersCount, 
			$totalPlayerCount, 
			$playingPlayersCount,
			$this->matchMakingService->getAverageTimeBetweenMatches($this->storage->serverLogin, $this->scriptName, $this->titleIdString)
		);
		
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

	protected function setMatchMaker(MatchMakers\MatchMakerInterface $matchMaker)
	{
		$this->matchMaker = $matchMaker;
	}

	protected function getMatchablePlayers()
	{
		$readyPlayers = Services\PlayerInfo::GetReady();
		$service = $this->matchMakingService;

		$serverLogin = $this->storage->serverLogin;
		$scriptName = $this->scriptName;
		$titleIdString = $this->titleIdString;
		$blockedPlayers = array_keys($this->blockedPlayers);

		$matchablePlayers = array_filter($readyPlayers,
			function (Services\PlayerInfo $p) use ($service, $serverLogin, $scriptName, $titleIdString, $blockedPlayers)
			{
				return !$service->isInMatch($p->login, $serverLogin, $scriptName, $titleIdString) && !in_array($p->login, $blockedPlayers) && !$p->isAway();
			});

		return array_map(function (Services\PlayerInfo $p) { return $p->login; }, $matchablePlayers);
	}

	protected function setReadyLabel()
	{
		$matchablePlayers = $this->getMatchablePlayers();
		if(count($matchablePlayers) < $this->matchMaker->getPlayersPerMatch())
		{
			$message = $this->gui->getNeedReadyPlayersText();
		}
		else
		{
			$message = $this->gui->getReadyText();
		}

		foreach($matchablePlayers as $login)
		{
			$this->gui->createLabel($message, $login);
		}
	}
}

?>
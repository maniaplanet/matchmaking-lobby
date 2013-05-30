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

	const PREFIX = '$000»$8f0 ';

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

	/**
	 * @var bool
	 */
	protected $backupNeeded = false;

	/**
	 * @var int[string]
	 */
	protected $matchCancellers = array();

	function onInit()
	{
		$this->setVersion('2.2.0');

		if (version_compare(\ManiaLiveApplication\Version, \ManiaLivePlugins\MatchMakingLobby\Config::REQUIRED_MANIALIVE) < 0)
		{
			throw new \ManiaLive\Application\FatalException(sprintf('You ManiaLive version is too old, please update to %s', \ManiaLivePlugins\MatchMakingLobby\Config::REQUIRED_MANIALIVE));
		}

		//Load MatchMaker and helpers for GUI
		$this->config = Config::getInstance();
		$script = $this->storage->gameInfos->scriptName;
		$this->scriptName = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance()->script ? : preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script);

		$matchMakerClassName = $this->config->getMatchMakerClassName($this->scriptName);
		if (!class_exists($matchMakerClassName))
		{
			throw new \Exception(sprintf("Can't find class %s. You should either set up the config : ManiaLivePlugins\MatchMakingLobby\Config.matchMakerClassName or the script name",$matchMakerClassName));
		}

		$guiClassName = $this->config->getGuiClassName($this->scriptName);
		if (!class_exists($guiClassName))
		{
			throw new \Exception(sprintf("Can't find class %s. You should either set up the config : ManiaLivePlugins\MatchMakingLobby\Config.guiClassName or the script name",$guiClassName));
		}

		$this->matchMakingService = new Services\MatchMakingService();
		$this->matchMakingService->createTables();

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

		$matchSettingsClass = $this->config->getMatchSettingsClassName($this->scriptName);
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

			$this->updateKarma($login);

			$help = Windows\Help::Create($login);
			$help->modeName = $this->scriptName;
			$help->displayHelp = ($playerObject->isSpectator ? true : false);
			$help->show();
		}
		$this->updatePlayerList = true;

		$this->setNotReadyLabel();

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

		$this->registerChatCommand('setAllReady', 'onSetAllReady', 0, true, \ManiaLive\Features\Admin\AdminGroup::get());
		$this->registerChatCommand('kickNonReady', 'onKickNotReady', 0, true, \ManiaLive\Features\Admin\AdminGroup::get());
		$this->registerChatCommand('resetPenalty', 'onResetPenalty', 1, true, \ManiaLive\Features\Admin\AdminGroup::get());
		$this->registerChatCommand('resetAllPenalties', 'onResetAllPenalties', 0, true, \ManiaLive\Features\Admin\AdminGroup::get());

	}

	function onUnload()
	{
		$this->setLobbyInfo(false);
		parent::onUnload();
	}

	function onPlayerConnect($login, $isSpectator)
	{
		\ManiaLive\Utilities\Logger::debug(sprintf('Player connected: %s', $login));

		$player = Services\PlayerInfo::Get($login);
		$player->setAway(false);

		$playerObject = $this->storage->getPlayerObject($login);

		if($playerObject)
		{
			$player->ladderPoints = $playerObject->ladderStats['PlayerRankings'][0]['Score'];
			$player->allies = $playerObject->allies;
		}

		$match = $this->matchMakingService->getPlayerCurrentMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);

		if($match)
		{
			\ManiaLive\Utilities\Logger::debug(sprintf('send %s to is match %d', $login, $match->id));

			$player->isInMatch = true;

			$jumper = Windows\ForceManialink::Create($login);
			$jumper->set('maniaplanet://#qjoin='.$match->matchServerLogin.'@'.$match->titleIdString);
			$jumper->show();
			$this->gui->createLabel($this->gui->getMatchInProgressText(), $login);
			return;
		}
		else
		{
			$player->isInMatch = false;
		}

		if(!$playerObject)
		{
			return;
		}

		$this->setNotReadyLabel($login);

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
		$this->connection->forceSpectator($login, 1);
	}

	function onPlayerDisconnect($login, $disconnectionReason)
	{
		\ManiaLive\Utilities\Logger::debug(sprintf('Player disconnected: %s (%s)', $login, $disconnectionReason));

		$player = Services\PlayerInfo::Get($login);
		$player->setAway();

		//Erase potential replacer jumper
		$this->gui->eraseJump($login);

		$match = $this->matchMakingService->getPlayerCurrentMatch($login, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		if($match)
		{
			//Erase potential jumper
			$this->gui->eraseJump($match->id);

			if (array_key_exists($login, $this->matchCancellers))
			{
				unset($this->matchCancellers[$login]);
			}

			if (array_key_exists($match->id, $this->countDown) && $this->countDown[$match->id] > 0)
			{
				$this->onPlayerCancelMatchStart($login);
			}
			if (array_key_exists($login, $this->replacerCountDown) && $this->replacerCountDown[$login] > 0)
			{
				$this->onPlayerCancelReplacement($login);
			}
		}

		if(array_key_exists($login, $this->blockedPlayers))
		{
			unset($this->blockedPlayers[$login]);
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
		if ($this->tick % 8)
		{
			$mtime = microtime(true);
			foreach($this->blockedPlayers as $login => $time)
			{
				$this->updateKarma($login);
			}
			$timers['blocked'] = microtime(true) - $mtime;
		}

		//If there is some match needing players
		//find backup in ready players and send them to the match server
		$mtime = microtime(true);
		$matchesNeedingBackup = $this->matchMakingService->getMatchesNeedingBackup($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		if ($matchesNeedingBackup)
		{
			$this->backupNeeded = true;
			$potentialBackups = $this->getMatchablePlayers();
			$storage = $this->storage;

			//removing player which has allies from the potential backups
			$potentialBackups = array_filter($potentialBackups, function ($login) use ($storage)
			{
				$obj = $storage->getPlayerObject($login);
				if($obj)
				{
					return !count($obj->allies);
				}
			});
			if ($potentialBackups)
			{
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
							$this->gui->createLabel($this->gui->getBackUpLaunchText($match), $backup, 0, false, false);
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
			else
			{
				$this->setReadyLabel();
			}
			unset($potentialBackups);
		}
		else
		{
			$this->backupNeeded = false;
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
				unset($matches, $match);
				$timers['match'] = microtime(true) - $mtime;
			}
			// No server available for this match
			else
			{
				$this->setReadyLabel();
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
					if($match && $match->state >= Match::PREPARED)
					{
						$this->matchMakingService->updatePlayerState($this->replacers[$login], $match->id, Services\PlayerInfo::PLAYER_STATE_REPLACED);
						$this->gui->showJump($login);
						$this->connection->addGuest($login, true);
						$this->connection->chatSendServerMessageToLanguage(array(
							array('Lang' => 'fr', 'Text' => self::PREFIX.sprintf('$<%s$> a rejoint son match comme remplaçant.', $player->nickName)),
							array('Lang' => 'en', 'Text' => self::PREFIX.sprintf('$<%s$> joined his match as a substitute.', $player->nickName)),
						));
					}
					else
					{
						\ManiaLive\Utilities\Logger::debug(sprintf('For replacer %s, match does not exist anymore', $login));
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
				case -10:
					//nobreak;
				case -5:
					$this->gui->eraseJump($matchId);
					$match = $this->matchMakingService->getMatch($matchId);
					$players = array_filter($match->players, function ($p) { return Services\PlayerInfo::Get($p)->isReady(); });
					if($players)
					{
						\ManiaLive\Utilities\Logger::debug('re-display jumper for: '.implode(',', $players));
						$this->gui->prepareJump($players, $match->matchServerLogin, $this->titleIdString, $matchId);
						$this->gui->showJump($matchId);
					}
					$this->countDown[$matchId] = $countDown;
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
								$nicknames[] = '$<'.\ManiaLib\Utils\Formatting::stripStyles($player->nickName).'$>';
								$this->connection->addGuest($player, true);
							}
						}
						$this->connection->chatSendServerMessageToLanguage(array(
							array('Lang' => 'fr', 'Text' => self::PREFIX.implode(' & ', $nicknames).' ont rejoint un match.'),
							array('Lang' => 'en', 'Text' => self::PREFIX.implode(' & ', $nicknames).' join a match.'),
						));
					}
					else
					{
						\ManiaLive\Utilities\Logger::debug(sprintf('jump cancel match state is : %d', $match->state));
						foreach($match->players as $player)
						{
							$this->setReadyLabel($player);
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
			\ManiaLive\Utilities\Logger::debug('NextMap');
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
		if($this->tick % 12 == 0)
		{
			if($this->scriptName == 'Elite' || $this->scriptName == 'Combo')
			{
				$endedMatches = $this->matchMakingService->getEndedMatchesSince($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
				foreach($endedMatches as $endedMatch)
				{
					if($endedMatch->team1)
					{
						$blue = implode(', ', array_map('\ManiaLib\Utils\Formatting::stripStyles', $endedMatch->team1));
						$red = implode(', ', array_map('\ManiaLib\Utils\Formatting::stripStyles', $endedMatch->team2));
					}
					else
					{
						$blue = \ManiaLib\Utils\Formatting::stripStyles($endedMatch->players[0]);
						$red = \ManiaLib\Utils\Formatting::stripStyles($endedMatch->players[1]);
					}

					$this->connection->chatSendServerMessage(sprintf(self::PREFIX.'$<$00f%s$> $o%d - %d$z $<$f00%s$>',$blue,$endedMatch->mapPointsTeam1, $endedMatch->mapPointsTeam2, $red));
				}
			}
		}

		if($this->updatePlayerList)
		{
			$this->gui->updatePlayerList($this->blockedPlayers);
			$this->updatePlayerList = false;
		}
		if ($this->tick % 12 == 0)
		{
			$this->registerLobby();
		}
		Services\PlayerInfo::CleanUp();

		$this->connection->executeMulticall();

		//Debug
		$timers = array_filter($timers, function($v) { return $v > 0.10; });
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

			$this->setReadyLabel($login);

			$this->updatePlayerList = true;

			$this->connection->forceSpectator($login, 2);
			$this->connection->forceSpectator($login, 0);
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

		$this->setNotReadyLabel($login);

		$this->updatePlayerList = true;

		$this->connection->forceSpectator($login, 1);

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

			//FIXME: it could have been QUITTER or GIVEUP
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
		if ($match !== false && $match->state == Match::PREPARED && array_key_exists($match->id, $this->countDown) && $this->countDown[$match->id] > 0)
		{
			$this->gui->eraseJump($match->id);
			unset($this->countDown[$match->id]);

			if (array_key_exists($login, $this->matchCancellers))
			{
				$this->matchCancellers[$login]++;

				if ($this->matchCancellers[$login] > $this->config->authorizedMatchCancellation)
				{
					$this->matchMakingService->increasePlayerPenalty($login, 45, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
					$this->blockedPlayers[$login] = time();
				}
			}
			else
			{
				$this->matchCancellers[$login] = 1;
			}

			$this->matchMakingService->cancelMatch($match);

			$this->matchMakingService->updatePlayerState($login, $match->id, Services\PlayerInfo::PLAYER_STATE_CANCEL);

			$this->connection->chatSendServerMessageToLanguage(array(
				array('Lang' => 'fr', 'Text' => sprintf(static::PREFIX.'$<%s$> a annulé le départ d\'un match.', $player->nickName)),
				array('Lang' => 'en', 'Text' => sprintf(static::PREFIX.'$<%s$> cancelled match start.', $player->nickName))
			));

			foreach($match->players as $playerLogin)
			{
				Services\PlayerInfo::Get($playerLogin)->isInMatch = false;
				$this->gui->eraseMatchSumUp($playerLogin);

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
				\ManiaLive\Utilities\Logger::debug(sprintf('error: player %s cancel unknown match start',$login));
			}
			else
			{
				\ManiaLive\Utilities\Logger::debug(sprintf('error: player %s cancel match start (%d) not in prepared mode',$login, $match->id));
			}
		}
	}

	public function onSetAllReady()
	{
		foreach(array_merge($this->storage->players, $this->storage->spectators) as $player)
		{
			if (!array_key_exists($player->login, $this->blockedPlayers))
			{
				$this->onPlayerReady($player->login);
			}
		}
	}

	public function onKickNotReady()
	{
		foreach(Services\PlayerInfo::GetNotReady() as $player)
		{
			$this->connection->kick($player->login);
		}
	}

	public function onResetPenalty($login)
	{
		$this->matchMakingService->decreasePlayerPenalty($login, 86000, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
	}

	public function onResetAllPenalties()
	{
		foreach (array_merge($this->storage->players, $this->storage->spectators) as $player)
		{
			$this->matchMakingService->decreasePlayerPenalty($player->login, 86000, $this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		}
	}

	private function prepareMatch($server, $match)
	{
		$id = $this->matchMakingService->registerMatch($server, $match, $this->scriptName, $this->titleIdString, $this->storage->serverLogin);
		\ManiaLive\Utilities\Logger::debug(sprintf('Preparing match %d on server: %s',$id, $server));
		\ManiaLive\Utilities\Logger::debug($match);

		$this->gui->prepareJump($match->players, $server, $this->titleIdString, $id);
		$this->countDown[$id] = 12;

		foreach($match->players as $player)
		{
			$this->gui->createLabel($this->gui->getLaunchMatchText(), $player, 10);
			$this->gui->showMatchSumUp($match, $player);
			$this->setShortKey($player, array($this, 'onPlayerCancelMatchStart'));
			Services\PlayerInfo::Get($player)->isInMatch = true;
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
					$this->connection->chatSendServerMessageToLanguage(
						array(
							array('Lang' => 'fr','Text' => sprintf(self::PREFIX.'$<%s$> est suspendu.', $player->nickName)),
							array('Lang' => 'en','Text' => sprintf(self::PREFIX.'$<%s$> is suspended.', $player->nickName)),
					));
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

	/**
	 * @param string $login If null, set message for all ready players
	 */
	protected function setReadyLabel($login = null)
	{
		$matchablePlayers = $this->getMatchablePlayers();
		$players = ($login === null) ? $matchablePlayers : array($login);
		if ($this->matchMakingService->countAvailableServer($this->storage->serverLogin, $this->scriptName, $this->titleIdString) <= 0)
		{
			$message = $this->gui->getNoServerAvailableText();
		}
		else if(count($matchablePlayers) < $this->matchMaker->getPlayersPerMatch())
		{
			$message = $this->gui->getNeedReadyPlayersText();
		}
		else
		{
			$message = $this->gui->getReadyText();
		}

		foreach($players as $login)
		{
			$this->gui->createLabel($message, $login);
			$this->setShortKey($login, array($this, 'onPlayerNotReady'));
		}
	}

	/**
	 * @param string $login If null, set message for all non ready players
	 */
	protected function setNotReadyLabel($login = null)
	{
		$players = ($login === null) ? Services\PlayerInfo::GetNotReady() : array(Services\PlayerInfo::Get($login));
		foreach ($players as $player)
		{
			if (!array_key_exists($player->login, $this->blockedPlayers))
			{
				$message = $this->gui->getNotReadyText();
				if (count(Services\PlayerInfo::GetReady()) == 0 && $this->backupNeeded)
				{
					$notReadyPlayersTexts = $this->gui->getNoReadyPlayers();
					foreach($message as $language => $messages)
					{
						foreach($messages as $key => $text)
						{
							$message[$language][$key] .= "\n".$notReadyPlayersTexts[$language][$key];
						}
					}
				}
				$this->setShortKey($player->login, array($this,'onPlayerReady'));
				$this->gui->createLabel($message, $player->login, null, false, true, true);
			}
		}
	}
}

?>

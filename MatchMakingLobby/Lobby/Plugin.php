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
	protected $blockedPlayers = array();

	/** @var Helpers\PenaltiesCalculator */
	protected $penaltiesCalculator;

	/** @var Services\MatchMakingService */
	protected $matchMakingService;

	/** @var string */
	protected $scriptName;

	/** @var string */
	protected $titleIdString;

	function onInit()
	{
		$this->setVersion('0.2');
		//Load MatchMaker and helpers for GUI
		$this->config = Config::getInstance();
		$script = $this->storage->gameInfos->scriptName;
		$this->scriptName = \ManiaLivePlugins\MatchMakingLobby\Config::getInstance()->script ? : preg_replace('~(?:.*?[\\\/])?(.*?)\.Script\.txt~ui', '$1', $script);

		$matchMakerClassName = $this->config->matchMakerClassName ? : __NAMESPACE__.'\MatchMakers\\'.$this->scriptName;
		$guiClassName = $this->config->guiClassName ? : '\ManiaLivePlugins\MatchMakingLobby\GUI\\'.$this->scriptName;
		$penaltiesCalculatorClassName = $this->config->penaltiesCalculatorClassName ? : __NAMESPACE__.'\Helpers\PenaltiesCalculator';

		$this->setGui(new $guiClassName());
		$this->gui->lobbyBoxPosY = 45;
		$this->setMatchMaker($matchMakerClassName::getInstance());
		$this->setPenaltiesCalculator(new $penaltiesCalculatorClassName);
	}

	function onLoad()
	{
		//Check if Lobby is not running with the match plugin
		if($this->isPluginLoaded('MatchMakingLobby/Match'))
		{
			throw new Exception('Lobby and match cannot be one the same server.');
		}
		$this->enableDedicatedEvents(
			ServerEvent::ON_PLAYER_CONNECT |
			ServerEvent::ON_PLAYER_DISCONNECT |
			ServerEvent::ON_PLAYER_ALLIES_CHANGED |
			ServerEvent::ON_BEGIN_MAP |
			ServerEvent::ON_PLAYER_INFO_CHANGED
		);
		$matchSettingsClass = $this->config->matchSettingsClassName ? : '\ManiaLivePlugins\MatchMakingLobby\MatchSettings\\'.$this->scriptName;
		/* @var $matchSettings \ManiaLivePlugins\MatchMakingLobby\MatchSettings\MatchSettings */
		$matchSettings = new $matchSettingsClass();
		$settings = $matchSettings->getLobbyScriptSettings();
		$this->connection->setModeScriptSettings($settings);

		$this->enableTickerEvent();

		$this->matchMakingService = new Services\MatchMakingService();
		$this->matchMakingService->createTables();

		$this->titleIdString = $this->connection->getSystemInfo()->titleId;

		$this->backLink = $this->storage->serverLogin.':'.$this->storage->server->password.'@'.$this->titleIdString;

		$this->setLobbyInfo();
		foreach(array_merge($this->storage->players, $this->storage->spectators) as $login => $obj)
		{
			$player = Services\PlayerInfo::Get($login);
			$player->ladderPoints = $this->storage->getPlayerObject($login)->ladderStats['PlayerRankings'][0]['Score'];
			$player->allies = $this->storage->getPlayerObject($login)->allies;
			$this->gui->createPlayerList($login);
			$this->onPlayerNotReady($login);
		}
		$this->gui->updatePlayerList($this->blockedPlayers);

		$this->registerLobby();

		$playersCount = $this->getReadyPlayersCount();
		$totalPlayerCount = $this->getTotalPlayerCount();

		$this->gui->updateLobbyWindow($this->storage->server->name, $playersCount, $totalPlayerCount, $this->getPlayingPlayersCount());

		$feedback = Windows\Feedback::Create();
		$feedback->setAlign('right', 'bottom');
		$feedback->setPosition(160.1, 75);
		$feedback->show();
	}

	function onUnload()
	{
		$this->setLobbyInfo(false);
		parent::onUnload();
	}

	function onPlayerConnect($login, $isSpectator)
	{
		\ManiaLive\Utilities\Logger::debug(sprintf('Player connected: %s', $login));
		if($this->matchMakingService->isInMatch($login))
		{
			$match = $this->matchMakingService->getPlayerCurrentMatch($login);
			\ManiaLive\Utilities\Logger::debug(sprintf('send %s to is match %d', $login, $match->id));
			$jumper = Windows\ForceManialink::Create($login);
			$jumper->set('maniaplanet://#qjoin='.$match->matchServerLogin.'@'.$match->titleIdString);
			$jumper->show();
			$this->gui->createLabel($this->gui->getMatchInProgressText(), $login);
			return;
		}

		$message = '';
		$player = Services\PlayerInfo::Get($login);
		$message = ($player->ladderPoints ? $this->gui->getPlayerBackLabelPrefix() : '').$this->gui->getNotReadyText();
		$player->setAway(false);
		$player->ladderPoints = $this->storage->getPlayerObject($login)->ladderStats['PlayerRankings'][0]['Score'];
		$player->allies = $this->storage->getPlayerObject($login)->allies;

		$this->gui->createLabel($message, $login, null, true);

//		$this->gui->createLabel($login, $message);
		$this->setShortKey($login, array($this, 'onPlayerReady'));

		$this->gui->createPlayerList($login, $this->blockedPlayers);
		$this->gui->updatePlayerList($this->blockedPlayers);

		$this->updateLobbyWindow();
		$this->updateKarma($login);

		//TODO Rework text
//		$this->gui->showSplash($login, $this->storage->server->name,
//			array(
//			'Your are on a Lobby server',
//			'We will search an opponent of your level to play with',
//			'Queue until we find a match and a server for you',
//			'You will be automatically switch between the lobby and the match server',
//			'To abort a match, click on Ready',
//			'Click on Ready when you are',
//			'Use your "Alt" key to free your mouse'
//			), array($this, 'doNotShow')
//		);
	}

	function onPlayerDisconnect($login)
	{
		\ManiaLive\Utilities\Logger::debug(sprintf('Player disconnected: %s', $login));

		$match = $this->matchMakingService->getPlayerCurrentMatch($login);
		if($this->matchMakingService->isInMatch($login) && array_key_exists($match->matchServerLogin, $this->countDown) && $this->countDown[$match->id] > 0)
		{
			$this->onCancelMatchStart($login);
		}

		$player = Services\PlayerInfo::Get($login);
		$player->setAway();
		$this->gui->removePlayerFromPlayerList($login);

		$this->updateLobbyWindow();
	}

	function onPlayerInfoChanged($playerInfo)
	{
		$playerInfo = Structures\Player::fromArray($playerInfo);
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
		foreach($this->blockedPlayers as $login => $countDown)
		{
			$this->blockedPlayers[$login] = --$countDown;
			if($this->blockedPlayers[$login] <= 0)
			{
				unset($this->blockedPlayers[$login]);
				$this->onPlayerNotReady($login);
			}
		}

		//If there is some match needing players
		//find backup in ready players and send them to the match server
		$matchesNeedingBackup = $this->matchMakingService->getMatchesNeedingBackup($this->storage->serverLogin, $this->scriptName, $this->titleIdString);
		$potentialBackups = $this->getMatchablePlayers();
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
			$backups = array();
			foreach ($quitters as $quitter)
			{
				$backup = $this->matchMaker->getBackup($quitter, $potentialBackupsForMatch);
				if ($backup)
				{
					$backups[] = $backup;
					unset($potentialBackupsForMatch[array_search($backup, $potentialBackupsForMatch)]);
					unset($potentialBackups[array_search($backup, $potentialBackups)]);
				}
			}
			if(count($backups) && count($backups) == count($quitters))
			{
				\ManiaLive\Utilities\Logger::debug(
					sprintf('match %d, %s will replace %s', $match->id, implode(' & ', $backups), implode(' & ', $quitters))
				);
				foreach($quitters as $quitter)
				{
					$this->matchMakingService->updatePlayerState($quitter, $match->id, Services\PlayerInfo::PLAYER_STATE_REPLACED);
				}
				foreach($backups as $backup)
				{
					$teamId = $match->getTeam(array_shift($quitters));
					$this->matchMakingService->addMatchPlayer($match->id, $backup, $teamId);
					$this->gui->createLabel($this->gui->getBackUpLaunchText(), $backup);
				}
				$this->gui->prepareJump($backups, $match->matchServerLogin, $match->titleIdString, $match->id);
				$this->countDown[$match->id] = 5;
			}
		}

		foreach(array_merge($this->storage->players, $this->storage->spectators) as $player)
		{
			$this->updateKarma($player->login);
		}

		if(++$this->tick % 5 == 0)
		{

			$matches = $this->matchMaker->run($this->getMatchablePlayers());
			foreach($matches as $match)
			{
				/** @var Match $match */
				$server = $this->matchMakingService->getAvailableServer(
					$this->storage->serverLogin,
					$this->scriptName,
					$this->titleIdString
				);
				if(!$server)
				{
					foreach($match->players as $login)
					{
						$this->gui->createLabel($this->gui->getNoServerAvailableText(), $login);
					}
				}
				else
				{
					//Match ready, let's prepare it !
					$this->prepareMatch($server, $match);
				}
			}
		}

		foreach($this->countDown as $matchId => $countDown)
		{
			switch(--$countDown)
			{
				case -1:
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
						$this->connection->executeMulticall();

						$this->connection->chatSendServerMessage(self::PREFIX.implode(' & ', $nicknames).' join their match server.', null);
					}
					else
					{
						\ManiaLive\Utilities\Logger::debug(sprintf('jump cancel match state is : %d', $match->state));
						foreach($match->players as $player)
						{
							$this->gui->createLabel($this->gui->getReadyText(), $player);
						}
					}
				default:
					$this->countDown[$matchId] = $countDown;
			}
		}

		if(++$this->mapTick % 1800 == 0)
		{
			$this->connection->nextMap();
		}

		if($this->tick % 30 == 0)
		{
			$logins = $this->matchMakingService->getPlayersJustFinishedAllMatches();
			foreach($logins as $login)
			{
				$this->connection->removeGuest($login, true);
			}
			$this->connection->executeMulticall();
		}

		$this->setLobbyInfo();
		$this->updateLobbyWindow();
		$this->registerLobby();
		Services\PlayerInfo::CleanUp();
	}

	function onPlayerReady($login)
	{
		$player = Services\PlayerInfo::Get($login);
		if (!$this->matchMakingService->isInMatch($login))
		{
			$player->setReady(true);
			$this->setShortKey($login, array($this, 'onPlayerNotReady'));

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

			$this->gui->updatePlayerList($this->blockedPlayers);

			$this->setLobbyInfo();
			$this->updateLobbyWindow();
		}
		else
		{
			\ManiaLive\Utilities\Logger::debug(sprintf('Player try to be ready while in match: %s', $login));
		}
	}

	function onPlayerNotReady($login)
	{
		$player = Services\PlayerInfo::Get($login);
		$player->setReady(false);
		$this->setShortKey($login, array($this, 'onPlayerReady'));
		$this->gui->createLabel($this->gui->getNotReadyText(), $login, null, true);

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

		$this->gui->updatePlayerList($this->blockedPlayers);

		$this->setLobbyInfo();
		$this->updateLobbyWindow();
	}

	function onPlayerAlliesChanged($login)
	{
		$player = $this->storage->getPlayerObject($login);
		if($player)
		{
			Services\PlayerInfo::Get($login)->allies = $player->allies;
		}
		$this->gui->updatePlayerList($this->blockedPlayers);
	}

	function doNotShow($login)
	{
		//TODO store data
		$this->gui->hideSplash($login);
	}

	function onCancelMatchStart($login)
	{
		\ManiaLive\Utilities\Logger::debug('Player cancel match start: '.$login);

		$match = $this->matchMakingService->getPlayerCurrentMatch($login);
		if ($match->state == Match::PREPARED)
		{
			$this->gui->eraseJump($match->id);
			unset($this->countDown[$match->id]);
			$this->matchMakingService->updateMatchState($match->id, Services\Match::PLAYER_CANCEL);

			$this->matchMakingService->updatePlayerState($login, $match->id, Services\PlayerInfo::PLAYER_STATE_CANCEL);
			$this->matchMakingService->updateServerCurrentMatchId(null, $match->matchServerLogin, $match->scriptName,
				$match->titleIdString);

			foreach($match->players as $playerLogin)
			{
				if($playerLogin != $login)
					$this->onPlayerReady($playerLogin);
				else
					$this->onPlayerNotReady($playerLogin);
			}
		}
		else
		{
			\ManiaLive\Utilities\Logger::debug(sprintf('error: player %s cancel match start (%d) not in prepared mode',$login, $match->id));
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
			$this->setShortKey($player, array($this, 'onCancelMatchStart'));
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

		$this->gui->updatePlayerList($this->blockedPlayers);
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
		if ($player)
		{
			$leavesCount = $this->getLeavesCount($login);

			$karma = $this->penaltiesCalculator->calculateKarma($login, $leavesCount);
			if($playerInfo->karma < $karma || array_key_exists($login, $this->blockedPlayers))
			{
				if(!array_key_exists($login, $this->blockedPlayers))
				{
					$penalty = $this->penaltiesCalculator->getPenalty($login, $karma);
					$this->blockedPlayers[$login] = 60 * $penalty;
					$this->connection->chatSendServerMessage(
						sprintf(self::PREFIX.'$<%s$> is suspended for leaving matchs.', $player->nickName, $penalty)
					);
				}

				$this->onPlayerNotReady($login);

				$this->gui->createLabel($this->gui->getBadKarmaText($this->blockedPlayers[$login]), $login);
				$this->resetShortKey($login);
				$this->gui->updatePlayerList($this->blockedPlayers);
			}
			$playerInfo->karma = $karma;
		}
		else
		{
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
		$this->gui->updateLobbyWindow($this->storage->server->name, $playersCount, $totalPlayerCount, $playingPlayersCount);
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

	protected function setPenaltiesCalculator(Helpers\PenaltiesCalculator $penaltiesCalculator)
	{
		$this->penaltiesCalculator = $penaltiesCalculator;
	}

	protected function getMatchablePlayers()
	{
		$readyPlayers = Services\PlayerInfo::GetReady();
		$service = $this->matchMakingService;
		$notInMathcPlayers = array_filter($readyPlayers,
			function (Services\PlayerInfo $p) use ($service)
			{
				return !$service->isInMatch($p->login);
			});
		$blockedPlayers = array_keys($this->blockedPlayers);
		$notBlockedPlayers = array_filter($notInMathcPlayers,
			function (Services\PlayerInfo $p) use ($blockedPlayers)
			{
				return !in_array($p->login, $blockedPlayers);
			});

		return array_map(function (Services\PlayerInfo $p) { return $p->login; }, $notBlockedPlayers);
	}

	private function createMagnifyLabel($login, $message)
	{
		$this->gui->createLabel($message, $login, null, true);
	}
}

?>
<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\GUI;

use ManiaLive\Gui\Windows\Shortkey;
use ManiaLive\Data\Storage;
use ManiaLivePlugins\MatchMakingLobby\Windows;
use ManiaLivePlugins\MatchMakingLobby\Services\Match;
use ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo;
use ManiaLivePlugins\MatchMakingLobby\Controls\Player;

abstract class AbstractGUI
{

	/**
	 * Configure the shortkey to use, to switch between ready and not ready state
	 * @var int
	 */
	public $actionKey = Shortkey::F6;

	/**
	 * Configure the position of the Lobby info window
	 * @var int
	 */
	public $lobbyBoxPosY = 0;

	/**
	 * If set to true, allies icon is displayed in the player list
	 * @var bool
	 */
	public $displayAllies = false;

	/**
	 * Returns the text to display when a player is not ready
	 * @return string
	 */
	function getNotReadyText()
	{
		return '$o$F90Press F6 to find a match$z';
	}

	/**
	 * Returns the text to display when a player is readydz
	 * @return string
	 */
	function getReadyText()
	{
		return 'Searching for a match, press F6 to cancel';
	}

	function getNeedReadyPlayersText()
	{
		return 'Waiting for more ready players';
	}

	/**
	 * Returns the prefix message that is displayed when a player comes back on the lobby
	 * @return string
	 */
	function getPlayerBackLabelPrefix()
	{
		return 'Welcome back. ';
	}

	/**
	 * Returns the text to display when all mathc servers are full
	 * @return string
	 */
	function getNoServerAvailableText()
	{
		return 'No match server available at the moment, please wait';
	}

	/**
	 *
	 * @return string
	 */
	function getIllegalLeaveText()
	{
		return "A player left\nDo not leave, searching for a substitute";
	}

	/**
	 * Returns the message displayed when a player is selected in a match
	 * @param Match $m The match that will be played
	 * @param string $player The login of a player in the match
	 * @return string
	 */
	abstract function getLaunchMatchText(Match $m, $player);

	/**
	 * Returns the message displayed when a player is picked up as a backup to replace
	 * a missing player
	 * @param string $player
	 * @return string
	 */
	function getBackUpLaunchText()
	{
		return '$0F0You are selected to be a substitute. Prepare to be transferred';
	}

	/**
	 * Returns the message when a player join the lobby and he have a match still running
	 * @return string
	 */
	function getMatchInProgressText()
	{
		return 'You have a match in progress. Prepare to be transferred';
	}

	/**
	 * Returns the message when a player is blocked
	 * @param int $time in seconds
	 * @return string
	 */
	function getBadKarmaText($time)
	{
		return sprintf("\$F00You left your last match\nYou are suspended for %d minutes", ceil($time / 60.));
	}

	/**
	 * Returns the message when the match is over
	 * @return string
	 */
	function getMatchoverText()
	{
		return 'Match over. You will be transferred back.';
	}

	/**
	 * Returns when a player give up
	 * @return string
	 */
	function getGiveUpText()
	{
		return "A player gave up\nDo not leave, searching for a substitute";
	}

	/**
	 * Returns the message when in DECIDING phase
	 * @return string
	 */
	function getDecidingText()
	{
		return 'Waiting for all player to connect before starting match';
	}

	/**
	 * Display a text message in the center of the player's screen
	 * If countdown is set, the message will be refresh every second the end of the countdown
	 * @param string $login
	 * @param string $message
	 * @param int $countdown
	 * @param bool $isAnimated If true the text will be animated
	 */
	final function createLabel($message, $login = null, $countdown = null, $isAnimated = false, $hideOnF6 = true)
	{
		if($login)
		{
			Windows\Label::Erase($login);
		}
		else
		{
			Windows\Label::EraseAll();
		}
		$ui = Windows\Label::Create($login);
		$ui->setPosition(0, 40);
		$ui->setMessage($message, $countdown);
		$ui->animated = $isAnimated;
		$ui->hideOnF6 = $hideOnF6;
		$ui->show();
	}

	/**
	 * Display the lobby Window on the right of the screen
	 * @param string $serverName
	 * @param int $playersCount Number of players ready on the lobby
	 * @param int $totalPlayerCount Total number of player on the matchmaking system
	 * @param int $playingPlayersCount Number of player in match
	 */
	final function updateLobbyWindow($serverName, $playersCount, $totalPlayerCount, $playingPlayersCount)
	{
		$lobbyWindow = Windows\LobbyWindow::Create();
		$lobbyWindow->setAlign('right','bottom');
		$lobbyWindow->setPosition(165, $this->lobbyBoxPosY);
		$lobbyWindow->set($serverName, $playersCount, $totalPlayerCount, $playingPlayersCount);
		$lobbyWindow->show();
	}

	/**
	 * Create the player list to display to a player
	 * @param string $login
	 * @param string[] $blockedPlayerList
	 */
	final function createPlayerList($login)
	{
		$playerList = Windows\PlayerList::Create($login);
		$playerList->setAlign('right');
		$playerList->setPosition(165, $this->lobbyBoxPosY + 3);
		$playerList->show();
	}

	/**
	 * update the Player list
	 * @param string $login
	 * @param string[] $blockedPlayerList
	 */
	final function updatePlayerList(array $blockedPlayerList)
	{
		$storage = Storage::getInstance();
		$playerLists = Windows\PlayerList::GetAll();
		$matchMakingService = new \ManiaLivePlugins\MatchMakingLobby\Services\MatchMakingService();
		foreach($playerLists as $playerList)
		{
			$currentPlayerObj = $storage->getPlayerObject($playerList->getRecipient());
			foreach(array_merge($storage->players, $storage->spectators) as $player)
			{
				if(PlayerInfo::Get($player->login)->isAway())
				{
					continue;
				}

				$playerInfo = PlayerInfo::Get($player->login);
				$playerObj = $storage->getPlayerObject($player->login);
				$state = Player::STATE_NOT_READY;
				if($playerInfo->isReady()) $state = Player::STATE_READY;
				if($matchMakingService->isInMatch($player->login)) $state = Player::STATE_IN_MATCH;
				if(array_key_exists($player->login, $blockedPlayerList)) $state = Player::STATE_BLOCKED;

				/* @var $playerList Windows\PlayerList */
				$isAlly = $this->displayAllies && $player && in_array($player->login, $currentPlayerObj->allies);
				$playerList->setPlayer($player->login, $state, $isAlly, $playerObj->ladderStats['PlayerRankings'][0]['Ranking']);
			}
		}
		Windows\PlayerList::RedrawAll();
	}

	/**
	 * Remove a player from the playerlist and destroy his list
	 * @param string $login
	 */
	final function removePlayerFromPlayerList($login)
	{
		Windows\PlayerList::Erase($login);

		$playerLists = Windows\PlayerList::GetAll();
		foreach($playerLists as $playerList)
		{
			$playerList->removePlayer($login);
		}
		Windows\PlayerList::RedrawAll();
	}

	final function prepareJump(array $players, $serverLogin, $titleIdString, $matchId)
	{
		$groupName = sprintf('match-%d',$matchId);
		$this->eraseJump($serverLogin);
		$group = \ManiaLive\Gui\Group::Create($groupName, $players);
		$jumper = Windows\ForceManialink::Create($group);
		$jumper->set('maniaplanet://#qjoin='.$serverLogin.'@'.$titleIdString);
	}

	final function eraseJump($matchId)
	{
		$groupName = sprintf('match-%d',$matchId);
		Windows\ForceManialink::Erase(\ManiaLive\Gui\Group::Get($groupName));
		\ManiaLive\Gui\Group::Erase($groupName);
	}

	final function showJump($matchId)
	{
		$groupName = sprintf('match-%d',$matchId);
		$group = \ManiaLive\Gui\Group::Get($groupName);
		Windows\ForceManialink::Create($group)->show();
	}

	final function showSplash($login, $serverName , array $lines, $callback)
	{
		$splash = Windows\Splash::Create($login);
		$splash->set('Welcome on '.$serverName, $lines,
			\ManiaLive\Gui\ActionHandler::getInstance()->createAction(array($this,'hideSplash')),
			\ManiaLive\Gui\ActionHandler::getInstance()->createAction($callback)
		);
		$splash->show();
	}

	final function hideSplash($login)
	{
		Windows\Splash::Erase($login);
	}

}

?>
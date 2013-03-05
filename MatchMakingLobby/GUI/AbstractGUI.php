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
	abstract function getNotReadyText();

	/**
	 * Returns the text to display when a player is ready
	 * @return string
	 */
	abstract function getReadyText();

	/**
	 * Returns the prefix message that is displayed when a player comes back on the lobby
	 * @return string
	 */
	abstract function getPlayerBackLabelPrefix();

	/**
	 * Returns the message displayed when a player is selected in a match
	 * @param Match $m The match that will be played
	 * @param string $player The login of a player in the match
	 * @return string
	 */
	abstract function getLaunchMatchText(Match $m, $player);

	/**
	 * Returns the message when a player join the lobby and he have a match still running
	 * @return string
	 */
	abstract function getMatchInProgressText();

	/**
	 * Returns the message when a player is blocked
	 * @param int $time in seconds
	 * @return string
	 */
	abstract function getBadKarmaText($time);

	/**
	 * Display a text message in the center of the player's screen
	 * If countdown is set, the message will be refresh every second the end of the countdown
	 * @param string $login
	 * @param string $message
	 * @param int $countdown
	 */
	final function createLabel($login, $message, $countdown = null)
	{
		Windows\Label::Erase($login);
		$confirm = Windows\Label::Create($login);
		$confirm->setPosition(0, 40);
		$confirm->setMessage($message, $countdown);
		$confirm->show();
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
		$lobbyWindow->setPosition(170, $this->lobbyBoxPosY);
		$lobbyWindow->set($serverName, $playersCount, $totalPlayerCount, $playingPlayersCount);
		$lobbyWindow->show();
	}

	/**
	 * Create the player list to display to a player
	 * @param string $login
	 * @param string[] $blockedPlayerList 
	 */
	final function createPlayerList($login, array $blockedPlayerList)
	{
		$storage = Storage::getInstance();
		$playerList = Windows\PlayerList::Create($login);
		$playerList->setAlign('right');
		$playerList->setPosition(170, $this->lobbyBoxPosY + 3);

		$currentPlayerObj = $storage->getPlayerObject($login);
		foreach(array_merge($storage->players, $storage->players) as $login => $object)
		{
			$playerInfo = PlayerInfo::Get($login);
			$state = 0;
			if($playerInfo->isReady()) $state = 1;
			if($playerInfo->isInMatch() && $this->isPlayerMatchExist($login)) $state = 2;
			if(array_key_exists($login, $blockedPlayerList)) $state = 3;
			$isAlly = ($this->displayAllies && $currentPlayerObj && in_array($login, $currentPlayerObj->allies));
			$playerList->setPlayer($login, $state, $isAlly);
		}
		$playerList->show();
	}

	/**
	 * update the Player list
	 * @param string $login
	 * @param string[] $blockedPlayerList
	 */
	final function updatePlayerList($login, array $blockedPlayerList)
	{
		$currentPlayerObj = Storage::getInstance()->getPlayerObject($login);
		$playerInfo = PlayerInfo::Get($login);
		$state = 0;
		if($playerInfo->isReady()) $state = 1;
		if($playerInfo->isInMatch()) $state = 2;
		if(array_key_exists($login, $blockedPlayerList)) $state = 3;

		$playerLists = Windows\PlayerList::GetAll();
		foreach($playerLists as $playerList)
		{
			/* @var $playerList Windows\PlayerList */
			$isAlly = $this->displayAllies && $currentPlayerObj && in_array($playerList->getRecipient(),
					$currentPlayerObj->allies);
			$playerList->setPlayer($login, $state, $isAlly);
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
			$playerList->redraw();
		}
		Windows\PlayerList::RedrawAll();
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
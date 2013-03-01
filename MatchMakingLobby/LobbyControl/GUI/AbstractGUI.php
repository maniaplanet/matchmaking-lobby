<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\LobbyControl\GUI;

use ManiaLive\Gui\Windows\Shortkey;
use ManiaLive\Data\Storage;
use ManiaLivePlugins\MatchMakingLobby\Windows;
use ManiaLivePlugins\MatchMakingLobby\LobbyControl\Match;
use ManiaLivePlugins\MatchMakingLobby\LobbyControl\PlayerInfo;

abstract class AbstractGUI
{

	public $actionKey = Shortkey::F6;
	public $lobbyBoxPosY = 0;
	public $displayAllies = false;

	abstract function getNotReadyText();

	abstract function getReadyText();

	abstract function getPlayerBackLabelPrefix();

	abstract function getLaunchMatchText(Match $m, $player);

	abstract function getMatchInProgressText();
	
	abstract function getBadKarmaText($time);
	
	final function createLabel($login, $message, $countdown = null)
	{
		Windows\Label::Erase($login);
		$confirm = Windows\Label::Create($login);
		$confirm->setPosition(0, 40);
		$confirm->setMessage($message, $countdown);
		$confirm->show();
	}
	
	final function updateLobbyWindow($serverName, $playersCount, $totalPlayerCount, $playingPlayersCount)
	{
		$lobbyWindow = Windows\LobbyWindow::Create();
		$lobbyWindow->set($serverName, $playersCount, $totalPlayerCount, $playingPlayersCount);
		$lobbyWindow->show();
	}
	
	final function createPlayerList($login, array $blockedPlayerList)
	{
		$storage = Storage::getInstance();
		$playerList = Windows\PlayerList::Create($login);
		$playerList->setAlign('right');
		$playerList->setPosition(170, 48);
		
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
			$isAlly = $this->displayAllies && $currentPlayerObj && in_array($playerList->getRecipient(), $currentPlayerObj->allies);
			$playerList->setPlayer($login, $state, $isAlly);
		}
		Windows\PlayerList::RedrawAll();
	}
	
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
}

?>
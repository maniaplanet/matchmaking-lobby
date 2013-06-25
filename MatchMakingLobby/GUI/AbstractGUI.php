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
	 * @return string[string][string]
	 */
	function getNotReadyText()
	{
		return 'notReadyPlayer';
	}

	/**
	 * Returns the text to display when a player is readydz
	 * @return string
	 */
	function getReadyText()
	{
		return 'readyPlayer';
	}

	function getNeedReadyPlayersText()
	{
		return 'needReadyPlayers';
	}

	/**
	 * Returns the prefix message that is displayed when a player comes back on the lobby
	 * @return string
	 */
	function getPlayerBackLabelPrefix()
	{
		return 'playerBackLabelPrefix';
	}

	/**
	 * Returns the text to display when all mathc servers are full
	 * @return string
	 */
	function getNoServerAvailableText()
	{
		return 'noServerAvailable';
	}

	/**
	 *
	 * @return string
	 */
	function getIllegalLeaveText()
	{
		return 'illegalLeave';
	}

	/**
	 * Returns the message displayed when a player is selected in a match
	 * @return string
	 */
	function getLaunchMatchText()
	{
		return 'launchMatch';
	}

	abstract function getCustomizedQuitDialogManiaLink();

	/**
	 * Returns the message displayed when a player is picked up as a backup to replace
	 * a missing player
	 * @param string $player
	 * @return string
	 */
	abstract function getBackUpLaunchText(Match $match);

	/**
	 * Returns the message when a player join the lobby and he have a match still running
	 * @return string
	 */
	function getMatchInProgressText()
	{
		return 'matchInProgress';
	}

	/**
	 * Returns the message when a player is blocked
	 * @param int $time in seconds
	 * @return string
	 */
	function getBadKarmaText($time)
	{
		return array('textId' => 'suspended', 'params' => array(ceil($time / 60)));
	}

	/**
	 * Returns the message when the match is over
	 * @return string
	 */
	function getMatchoverText()
	{
		return 'matchOver';
	}

	/**
	 * Returns when a player give up
	 * @return string
	 */
	function getGiveUpText()
	{
		return 'giveUp';
	}

	/**
	 * Returns the message when in DECIDING phase
	 * @return string
	 */
	function getDecidingText()
	{
		return 'deciding';
	}

	/**
	 * Message displayed when a player has too many allies for the current mode
	 * @param int $n
	 * @return string
	 */
	function getTooManyAlliesText($n)
	{
		return array('textId' => 'tooManyAllies', 'params' => $n);
	}

	/**
	 * Message displayed to all non ready players to tell them that they can join a match as substitute
	 * @return type
	 */
	function getNoReadyPlayers()
	{
		return 'expectingReadyPlayers';
	}

	/**
	 * Display a text message in the center of the player's screen
	 * If countdown is set, the message will be refresh every second the end of the countdown
	 * @param string $login
	 * @param string $message
	 * @param int $countdown
	 * @param bool $isAnimated If true the text will be animated
	 */
	final function createLabel($message, $login = null, $countdown = null, $isAnimated = false, $hideOnF6 = true, $showBackgroud = false)
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
		$ui->showBackground = $showBackgroud;
		$ui->show();
	}

	final function showMatchSumUp(Match $match, $receiver)
	{
		$storage = Storage::getInstance();
		$getPlayerInfosCallback = function ($login) use ($storage)
			{
				$p = $storage->getPlayerObject($login);
				if($p)
				{
					$pathArray = explode('|', $p->ladderStats['PlayerRankings'][0]['Path']);
					$nickname = $p->nickName;
					$zone = array_pop($pathArray);
					$rank = $p->ladderStats['PlayerRankings'][0]['Ranking'];
				}
				else
				{
					$nickname = $login;
					$zone = 'World';
					$rank = -1;
				}
				return (object) array(
					'nickname' => $nickname,
					'zone' => $zone,
					'rank' => $rank
				);
			};
		if($match->team1 && $match->team2)
		{
			$team1 = array_map($getPlayerInfosCallback, $match->team1);
			$team2 = array_map($getPlayerInfosCallback, $match->team2);
		}
		else
		{
			$team1 = array(call_user_func($getPlayerInfosCallback, $match->players[0]));
			$team2 = array(call_user_func($getPlayerInfosCallback, $match->players[1]));
		}
		$window = Windows\StartMatch::Create($receiver);
		$window->set($team1, $team2);
		$window->setPosY(11);
		$window->show();
	}

	final function eraseMatchSumUp($receiver)
	{
		Windows\StartMatch::Erase($receiver);
	}

	/**
	 * Display the lobby Window on the right of the screen
	 * @param string $serverName
	 * @param int $playersCount Number of players ready on the lobby
	 * @param int $totalPlayerCount Total number of player on the matchmaking system
	 * @param int $playingPlayersCount Number of player in match
	 */
	final function updateLobbyWindow($serverName, $playersCount, $playingPlayersCount, $averageTime)
	{
		$lobbyWindow = Windows\LobbyWindow::Create();
		$lobbyWindow->setAlign('right','bottom');
		$lobbyWindow->setPosition(165, $this->lobbyBoxPosY);
		$lobbyWindow->set($serverName, $playersCount, $playingPlayersCount, $averageTime);
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
				//FIXME: how to be sure that he is in match?
				if($playerInfo->isInMatch) $state = Player::STATE_IN_MATCH;
				if(array_key_exists($player->login, $blockedPlayerList)) $state = Player::STATE_BLOCKED;

				/* @var $playerList Windows\PlayerList */
				$isAlly = $this->displayAllies && $player && in_array($player->login, $currentPlayerObj->allies);
				$path = explode('|',$playerObj->ladderStats['PlayerRankings'][0]['Path']);
				$zone = array_pop($path);
				$rank = $playerObj->ladderStats['PlayerRankings'][0]['Ranking'];
				$ladderPoints = $playerObj->ladderStats['PlayerRankings'][0]['Score'];
				$playerList->setPlayer($player->login, $state, $isAlly, $rank, $zone, $ladderPoints);
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
		$groupName = sprintf('match-%s',$matchId);
		$this->eraseJump($serverLogin);
		$group = \ManiaLive\Gui\Group::Create($groupName, $players);
		$jumper = Windows\ForceManialink::Create($group);
		$jumper->setPosition(0, 21.5);
		$jumper->set('maniaplanet://#qjoin='.$serverLogin.'@'.$titleIdString);
	}

	final function eraseJump($matchId)
	{
		$groupName = sprintf('match-%s',$matchId);
		Windows\ForceManialink::Erase(\ManiaLive\Gui\Group::Get($groupName));
		\ManiaLive\Gui\Group::Erase($groupName);
	}

	final function showJump($matchId)
	{
		$groupName = sprintf('match-%s',$matchId);
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
	
	final function showHelp($login, $scriptName, $displayHelp = false)
	{
		Windows\Help::Erase($login);
		$help = Windows\Help::Create($login);
		$help->displayHelp = $displayHelp;
		$help->modeName = $scriptName;
		$help->show();
	}

}

?>
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
	
	protected $nonReadyGroupName = 'nonReadyPlayers';
	protected $readyGroupName = 'readyPlayers';

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
		return array('textId' => 'tooManyAllies', 'params' => array($n));
	}

	/**
	 * Message displayed to all non ready players to tell them that they can join a match as substitute
	 * @return type
	 */
	function getNoReadyPlayers()
	{
		return 'expectingReadyPlayers';
	}
	
	function getTransferText()
	{
		return 'transfer';
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
		$this->removeLabel($login);
		$ui = Windows\Label::Create($login);
		$ui->setPosition(0, 40);
		$ui->setMessage($message, $countdown);
		$ui->animated = $isAnimated;
		$ui->hideOnF6 = $hideOnF6;
		$ui->showBackground = $showBackgroud;
		$ui->show();
	}
	
	final function removeLabel($login = null)
	{
		if($login)
		{
			Windows\Label::Erase($login);
		}
		else
		{
			Windows\Label::EraseAll();
		}
	}

	function showMatchSumUp(Match $match, $receiver, $time)
	{
		$storage = Storage::getInstance();
		$getPlayerInfosCallback = function ($login) use ($storage)
			{
				$p = $storage->getPlayerObject($login);
				$pathArray = explode('|', $p->ladderStats['PlayerRankings'][0]['Path']);
				$path = implode('|', array_slice($pathArray, 0, 3));
				return (object) array(
					'login' => $login,
					'nickname' => ($p ? $p->nickName : $login),
					'zone' => ($p ? array_pop($pathArray) : 'World'),
					'rank' => ($p ? $p->ladderStats['PlayerRankings'][0]['Ranking'] : -1),
					'zoneFlag' => sprintf('file://ZoneFlags/Login/%s/country', $login),
					'ladderPoints' => $p->ladderStats['PlayerRankings'][0]['Score'],
					'echelon' => floor($p->ladderStats['PlayerRankings'][0]['Score'] / 10000)
				);
			};
		$sortPlayerCallback = function ($player1, $player2)
		{
			if($player1->ladderPoints == $player2->ladderPoints)
			{
				return 0;
			}
			return $player1->ladderPoints < $player2->ladderPoints ? 1 : -1;
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
		usort($team1, $sortPlayerCallback);
		usort($team2, $sortPlayerCallback);
		$window = Windows\StartMatch::Create($receiver);
		$window->set($team1, $team2, $time);
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
		Windows\LobbyWindow::setServerName($serverName);
		Windows\LobbyWindow::setAverageWaitingTime($averageTime == -1 ?  -1 : ceil($averageTime/60));
		Windows\LobbyWindow::setPlayingPlayerCount($playingPlayersCount);
		Windows\LobbyWindow::setReadyPlayerCount($playersCount);
		$lobbyWindow->show();
	}

	/**
	 * Create the player list to display to a player
	 */
	final function createPlayerList($login)
	{
		$playerList = Windows\PlayerList::Create($login);
		$playerList->show();
	}
	
	final function createMasterList()
	{
		$masterList = Windows\MasterList::Create();
		$masterList->show();
	}
	
	final function updateMasterList(array $masters)
	{
		foreach ($masters as $master)
		{
			Windows\MasterList::addMaster($master['login'], $master['nickName'], $master['ladderPoints']);
		}
	}
	
	final function showWaitingScreen($login)
	{
		$waitingScreen = Windows\WaitingScreen::Create($login);
		$waitingScreen->clearParty();
		$allies = \ManiaLivePlugins\MatchMakingLobby\Services\AllyService::getInstance()->getAll($login);
		$bilateralAllies = array();
		$unilateralAllies = array();
		foreach($allies as $ally)
		{
			if($ally->isBilateral)
			{
				$bilateralAllies[] = $ally->login;
			}
			else
			{
				$unilateralAllies[] = $ally->login;
			}
		}
		$party = array_merge(array($login), $bilateralAllies);
		
		$waitingScreen->createParty($party, $unilateralAllies);
		$waitingScreen->show();
	}
	
	final function createWaitingScreen($readyAction, $scriptName, $partySize, $rulesManialink, $logoURL = '', $logoLink = '')
	{
		Windows\WaitingScreen::setReadyAction($readyAction);
		Windows\WaitingScreen::setScriptName($scriptName);
		Windows\WaitingScreen::setPartySize($partySize);
		Windows\WaitingScreen::setRulesManialink($rulesManialink);
		Windows\WaitingScreen::setLogo($logoURL, $logoLink);
	}
	
	final function removeWaitingScreen($login)
	{
		Windows\WaitingScreen::Erase($login);
	}
	
	final function updateWaitingScreenLabel($textId, $login = null)
	{
		if($login)
		{
			$screens = Windows\WaitingScreen::Get($login);
		}
		else
		{
			$screens = Windows\WaitingScreen::GetAll();
		}
		foreach($screens as $screen)
		{
			$screen->setTextId($textId);
			$screen->redraw();
		}
	}
	
	function disableReadyButton($login, $disable = true)
	{
		$screens = Windows\WaitingScreen::Get($login);
		foreach($screens as $screen)
		{
			$screen->disableReadyButton($disable);
			$screen->redraw();
		}
	}

	/**
	 * update the Player list
	 * @param string $login
	 * @param string[] $blockedPlayerList
	 */
	final function updatePlayerList(array $blockedPlayerList, $setLocalAllyAction, $unsetLocalAllyAction, $maxAllyCount, $player = '')
	{
		if($player)
		{
			$playerLists = Windows\PlayerList::Get($player);
		}
		else
		{
			$playerLists = Windows\PlayerList::GetAll();
		}
		foreach($playerLists as $playerList)
		{
			$recipient = $playerList->getRecipient();
			$allies = \ManiaLivePlugins\MatchMakingLobby\Services\AllyService::getInstance()->getAll($recipient);
			$loginsWithUnsetAction = array_map(function (\ManiaLivePlugins\MatchMakingLobby\Services\Ally $a) 
			{
				if($a->type == \ManiaLivePlugins\MatchMakingLobby\Services\Ally::TYPE_LOCAL)
				{
					return $a->login;
				}
			}, $allies);
			$loginsWithNoAction = array_map(function (\ManiaLivePlugins\MatchMakingLobby\Services\Ally $a) 
			{
				if($a->type == \ManiaLivePlugins\MatchMakingLobby\Services\Ally::TYPE_GENERAL)
				{
					return $a->login;
				}
			}, $allies);
			
			$bilateralAllies = array_map(function (\ManiaLivePlugins\MatchMakingLobby\Services\Ally $a)
			{
				if($a->isBilateral)
				{
					return $a->login;
				}
			}, $allies);
			$storage = Storage::getInstance();
			foreach(array_merge($storage->players, $storage->spectators) as $player)
			{
				if(PlayerInfo::Get($player->login)->isAway())
				{
					continue;
				}
				$ladderPoints = $player->ladderStats['PlayerRankings'][0]['Score'];

				$playerInfo = PlayerInfo::Get($player->login);
				$state = Player::STATE_NOT_READY;
				if($playerInfo->isReady()) $state = Player::STATE_READY;
				if($playerInfo->isInMatch) $state = Player::STATE_IN_MATCH;
				if(array_key_exists($player->login, $blockedPlayerList)) $state = Player::STATE_BLOCKED;
				
				if($player->login == $recipient)
				{
					$action = null;
					$isAlly = false;
				}
				elseif(in_array($player->login, $loginsWithNoAction))
				{
					$action = null;
					$isAlly = true;
				}
				elseif(in_array($player->login, $loginsWithUnsetAction))
				{
					$action = $unsetLocalAllyAction;
					$isAlly = true;
				}
				else
				{
					$action = (count($allies) >= $maxAllyCount ? null : $setLocalAllyAction);
					$isAlly = false;
				}
				$isBilateral = in_array($player->login, $bilateralAllies);

				$playerList->setPlayer($player->login, $player->nickName, $ladderPoints, $state, $action, $isAlly, $isBilateral);
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

	final function showSplash($login, $backgroudnUrl, $clickCallBack, $closeCallBack)
	{
		$ah = \ManiaLive\Gui\ActionHandler::getInstance();
		$splash = Windows\Splash::Create($login);
		$splash->setBackgroundUrl($backgroudnUrl);
		$splash->setBackgroundClickAction($ah->createAction($clickCallBack));
		$splash->setCloseAction($ah->createAction($closeCallBack));
		$splash->show();
	}

	final function hideSplash($login)
	{
		Windows\Splash::Erase($login);
	}
	
	final function showDemoReadyDialog($login, $answerYesCallBack, $answerNoCallback)
	{
		$ah = \ManiaLive\Gui\ActionHandler::getInstance();
		
		$window = Windows\AlertReady::Create($login);
		$window->yesAction = $ah->createAction($answerYesCallBack);
		$window->noAction = $ah->createAction($answerNoCallback);
		$window->show();
	}
	
	final function removeDemoReadyDialog($login)
	{
		Windows\AlertReady::Erase($login);
	}
	
	final function showDemoPlayDialog($login, $answerYesCallBack, $answerNoCallback)
	{
		$ah = \ManiaLive\Gui\ActionHandler::getInstance();
		
		$window = Windows\AlertPlay::Create($login);
		$window->yesAction = $ah->createAction($answerYesCallBack);
		$window->noAction = $ah->createAction($answerNoCallback);
		$window->show();
	}
	
	final function removeDemoPayDialog($login)
	{
		Windows\AlertPay::Erase($login);
	}
	
	final function ShowTooManyAlliesLabel($login, $maxAlliesAllowed)
	{
		$tooManyAlly = Windows\TooManyAllies::Create($login);
		$tooManyAlly->setPosition(0, 60);
		$tooManyAlly->setText($this->getTooManyAlliesText($maxAlliesAllowed));
		$tooManyAlly->show();
	}
	
	final function eraseTooManyAlliesLabel($login)
	{
		
	}
	
}

?>
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
		return array(
			'fr' => array(
				'text' => '$o$F90Appuyez sur F6 pour jouer un match'
			),
			'en' => array(
				'text' => '$o$F90Press F6 to play a match$z'
			),
		);
	}

	/**
	 * Returns the text to display when a player is readydz
	 * @return string
	 */
	function getReadyText()
	{
		return array(
			'fr' => array(
				'text' => 'Recherche de match en cours, appuyez sur F6 pour annuler'
			),
			'en' => array(
				'text' => 'Searching for a match, press F6 to cancel'
			),
		);
	}

	function getNeedReadyPlayersText()
	{
		return array(
			'fr' => array(
				'text' => 'En attente de plus de joueurs prêts'
			),
			'en' => array(
				'text' => 'Waiting for more ready players'
			),
		);
	}

	/**
	 * Returns the prefix message that is displayed when a player comes back on the lobby
	 * @return string
	 */
	function getPlayerBackLabelPrefix()
	{
		return array(
			'fr' => array(
				'text' => 'Bienvenue. '
			),
			'en' => array(
				'text' => 'Welcome back. '
			),
		);
	}

	/**
	 * Returns the text to display when all mathc servers are full
	 * @return string
	 */
	function getNoServerAvailableText()
	{
		return array(
			'fr' => array(
				'text' => 'Aucun serveur de match disponible pour l\'instant, veuillez patienter'
			),
			'en' => array(
				'text' => 'No match server available at the moment, please wait'
			),
		);
	}

	/**
	 *
	 * @return string
	 */
	function getIllegalLeaveText()
	{
		return array(
			'fr' => array(
				'text' => "Un joueur est parti\nNe partez pas, un remplaçant est recherché"
			),
			'en' => array(
				'text' => "A player left\nDo not leave, searching for a substitute"
			),
		);
	}

	/**
	 * Returns the message displayed when a player is selected in a match
	 * @return string
	 */
	function getLaunchMatchText()
	{
		return array(
			'fr' => array(
				'text' => "\$0F0Votre match commence dans \$<\$FFF%1 \$>...\nF6 pour annuler"
			),
			'en' => array(
				'text' =>  "\$0F0Match starts in \$<\$FFF%1 \$>...\nF6 to cancel"
			),
		);
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
		return array(
			'fr' => array(
				'text' =>  "Vous avez un match en cours. Préparez-vous au transfert"
			),
			'en' => array(
				'text' =>  "You have a match in progress. Prepare to be transferred"
			),
		);
	}

	/**
	 * Returns the message when a player is blocked
	 * @param int $time in seconds
	 * @return string
	 */
	function getBadKarmaText($time)
	{
		return array(
			'fr' => array(
				'text' =>  sprintf("\$F00Vous êtes suspendu pour %d minutes", ceil($time / 60.))
			),
			'en' => array(
				'text' =>  sprintf("\$F00You are suspended for %d minutes", ceil($time / 60.))
			),
		);
	}

	/**
	 * Returns the message when the match is over
	 * @return string
	 */
	function getMatchoverText()
	{
		return array(
			'fr' => array(
				'text' =>  "Match terminé. Vous allez être retransféré"
			),
			'en' => array(
				'text' =>  'Match over. You will be transferred back.'
			),
		);
	}

	/**
	 * Returns when a player give up
	 * @return string
	 */
	function getGiveUpText()
	{
		return array(
			'fr' => array(
				'text' =>  "Un joueur a abandonné\nNe quittez pas, un remplaçant est recherché"
			),
			'en' => array(
				'text' =>  "A player gave up\nDo not leave, searching for a substitute"
			),
		);
	}

	/**
	 * Returns the message when in DECIDING phase
	 * @return string
	 */
	function getDecidingText()
	{
		return array(
			'fr' => array(
				'text' =>  "En attente de tous les joueurs avant de commencer le match"
			),
			'en' => array(
				'text' =>  'Waiting for all player to connect before starting match'
			),
		);
	}

	/**
	 * Message displayed when a player has too many allies for the current mode
	 * @param int $n
	 * @return string
	 */
	function getTooManyAlliesText($n)
	{
		return array(
			'fr' => array(
				'text' =>  sprintf("\$F00Vous avez trop d'alliés, le maximum est %d", $n)
			),
			'en' => array(
				'text' =>  sprintf("\$F00You have too many allies, maximum is %d", $n)
			),
		);
	}

	/**
	 * Message displayed to all non ready players to tell them that they can join a match as substitute
	 * @return type
	 */
	function getNoReadyPlayers()
	{
		return array(
			'fr' => array(
				'text' =>  sprintf("Un match est en attente de remplaçant")
			),
			'en' => array(
				'text' => sprintf("A match is expecting a substitute")
			),
		);
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
		$getNicknameCallback = function ($login) use ($storage)
			{
				$p = $storage->getPlayerObject($login);
				return ($p ? $p->nickName : $login);
			};
		if($match->team1 && $match->team2)
		{
			$team1 = array_map($getNicknameCallback, $match->team1);
			$team2 = array_map($getNicknameCallback, $match->team2);
		}
		else
		{
			$team1 = array(call_user_func($getNicknameCallback, $match->players[0]));
			$team2 = array(call_user_func($getNicknameCallback, $match->players[1]));
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
	final function updateLobbyWindow($serverName, $playersCount, $totalPlayerCount, $playingPlayersCount, $averageTime)
	{
		$lobbyWindow = Windows\LobbyWindow::Create();
		$lobbyWindow->setAlign('right','bottom');
		$lobbyWindow->setPosition(165, $this->lobbyBoxPosY);
		$lobbyWindow->set($serverName, $playersCount, $totalPlayerCount, $playingPlayersCount, $averageTime);
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
				//FIXME: how to be sure that he is in match?
				if($playerInfo->isInMatch) $state = Player::STATE_IN_MATCH;
				if(array_key_exists($player->login, $blockedPlayerList)) $state = Player::STATE_BLOCKED;

				/* @var $playerList Windows\PlayerList */
				$isAlly = $this->displayAllies && $player && in_array($player->login, $currentPlayerObj->allies);
				$path = explode('|',$playerObj->ladderStats['PlayerRankings'][0]['Path']);
				$zone = array_pop($path);
				$playerList->setPlayer($player->login, $state, $isAlly, $playerObj->ladderStats['PlayerRankings'][0]['Ranking'], $zone);
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

}

?>
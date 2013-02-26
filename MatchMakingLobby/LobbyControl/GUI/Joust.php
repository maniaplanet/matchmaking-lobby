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
use ManiaLivePlugins\MatchMakingLobby\LobbyControl\Match;

class Joust extends AbstractGUI
{

	public $actionKey = Shortkey::F6;
	public $lobbyBoxPosY = 45;
	public $displayAllies = false;

	public function getLaunchMatchText(Match $m, $player)
	{
		$key = array_search($player, $m->players);
		$opponentObj = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($m->players[($key + 1) % 2]);
		$opponent = ($opponentObj ? $opponentObj->nickName : $m->players[($key + 1) % 2]);
		return sprintf('$0F0Match against $<%s$> starts in $<$FFF%%2d$>, F6 to cancel...', $opponent);
	}

	public function getNotReadyText()
	{
		return 'Press F6 to find a match.';
	}

	public function getPlayerBackLabelPrefix()
	{
		return 'Welcome back. ';
	}

	public function getReadyText()
	{
		return 'Searching for an opponent, F6 to cancel.';
	}

	public function getMatchInProgressText()
	{
		return 'You have a match in progress. Prepare to be transfered.';
	}
	
	public function getBadKarmaText($time)
	{
		return sprintf('$F00You leaved your last match. You are suspended for %d minutes', $time);
	}

}

?>
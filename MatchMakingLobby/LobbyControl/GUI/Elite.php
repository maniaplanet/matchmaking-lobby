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

class Elite extends AbstractGUI
{

	public $actionKey = Shortkey::F6;
	public $lobbyBoxPosY = 0;
	public $displayAllies = true;

	public function getLaunchMatchText(Match $m, $player)
	{
		$key = array_search($player, $m->team1);
		if($key !== false)
		{
			$mate1Obj = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($m->team1[($key + 1) % 3]);
			$mate1 = ($mate1Obj ? $mate1Obj->nickName : $m->team1[($key + 1) % 3]);
			$mate2Obj = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($m->team1[($key + 2) % 3]);
			$mate2 = ($mate2Obj ? $mate2Obj->nickName : $m->team1[($key + 2) % 3]);
		}
		else
		{
			$key = array_search($player, $m->team2);
			$mate1Obj = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($m->team2[($key + 1) % 3]);
			$mate1 = ($mate1Obj ? $mate1Obj->nickName : $m->team2[($key + 1) % 3]);
			$mate2Obj = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($m->team2[($key + 2) % 3]);
			$mate2 = ($mate2Obj ? $mate2Obj->nickName : $m->team2[($key + 2) % 3]);
		}
		return sprintf('$0F0Match with $<%s$> & $<%s$> starts in $<$FFF%%2d$>, F6 to cancel...', $mate1, $mate2);
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
		$time = ceil($time / 60.);
		return sprintf('$F00You leaved your last match. You are suspended for %d minutes', $time);
	}

}

?>
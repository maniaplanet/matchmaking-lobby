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
use ManiaLivePlugins\MatchMakingLobby\Services\Match;

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
		return sprintf('$0F0Match against $<%s$> starts in $<$FFF%%1 $>'."\n".'F6 to cancel...', $opponent);
	}

	function getIllegalLeaveText()
	{
		return "A player left\nDo not leave, you will be transfered back";
	}

	function getGiveUpText()
	{
		return "A player gave up\nDo not leave, you will be transfered back";
	}
	
	function getCustomizedQuitDialogManiaLink()
	{
		$manialink = new \ManiaLivePlugins\MatchMakingLobby\Views\CustomizedQuitDialog('Do you really want to give up ?');
		return $manialink->display();
	}
}

?>
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

class Joust extends AbstractGUI
{

	public $actionKey = Shortkey::F6;
	public $lobbyBoxPosY = 45;
	public $displayAllies = false;

	function getIllegalLeaveText()
	{
		return 'illegalLeave';
	}

	function getGiveUpText()
	{
		return 'giveUp';
	}
	
	function getCustomizedQuitDialogManiaLink()
	{
		$message = array('text' => 'quitMatchQuestion','available' => 'available');
		$manialink = new \ManiaLivePlugins\MatchMakingLobby\Views\CustomizedQuitDialog($message);
		return $manialink->display();
	}
	
	function getBackUpLaunchText(\ManiaLivePlugins\MatchMakingLobby\Services\Match $match)
	{
		return 'backUpTransfert';
	}
}

?>

<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\GUI;

/**
 * Extend this if your are running a team mode script
 */
abstract class AbstractTeam extends AbstractGUI
{
	public $displayAllies = true;

	function getCustomizedQuitDialogManiaLink()
	{
		$message = array('text' => 'quitMatchQuestion','available' => 'available');
		$manialink = new \ManiaLivePlugins\MatchMakingLobby\Views\CustomizedQuitDialog($message);
		return $manialink->display();
	}
	
	function getBackUpLaunchText(\ManiaLivePlugins\MatchMakingLobby\Services\Match $match)
	{
		return array('textId' => 'backUpTransfert', 'params' => array($match->mapPointsTeam1, $match->mapPointsTeam2));
	}
}
?>
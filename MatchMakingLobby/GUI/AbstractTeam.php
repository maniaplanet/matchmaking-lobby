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
		return array(
			'fr' => array(
				'text' =>  sprintf("\$0F0Vous êtes sélectionnés comme remplaçant. Préparez-vous au transfert\nLe score est %d - %d. Appuyez sur F6 pour annuler", $match->mapPointsTeam1, $match->mapPointsTeam2)
			),
			'en' => array(
				'text' =>  sprintf("\$0F0You are selected to be a substitute. Prepare to be transferred\nScore is %d - %d. Press F6 to cancel", $match->mapPointsTeam1, $match->mapPointsTeam2)
			),
		);
	}
}
?>
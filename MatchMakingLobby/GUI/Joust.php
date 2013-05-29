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
		return array(
			'fr' => array(
				'text' =>  "Votre opposant a quitté le match\nNe partez pas, vous allez être retransféré"
			),
			'en' => array(
				'text' =>  "Your opponent left\nDo not leave, you will be transfered back"
			),
		);
	}

	function getGiveUpText()
	{
		return array(
			'fr' => array(
				'text' =>  "Votre opposant a abandonné\nNe partez pas, vous allez être retransféré"
			),
			'en' => array(
				'text' =>  "Your opponent gave up\nDo not leave, you will be transfered back"
			),
		);
	}
	
	function getCustomizedQuitDialogManiaLink()
	{
		$message = array(
			'fr' => array(
				'text' =>  'Voulez-vous vraiment abandonner ?',
				'available' => 'Disponible dans :'
			),
			'en' => array(
				'text' =>  'Do you really want to give up?',
				'available' => 'Available in:'
			),
		);
		$manialink = new \ManiaLivePlugins\MatchMakingLobby\Views\CustomizedQuitDialog($message);
		return $manialink->display();
	}
	
	function getBackUpLaunchText(\ManiaLivePlugins\MatchMakingLobby\Services\Match $match)
	{
		return array(
			'fr' => array(
				'text' =>  "\$0F0Vous êtes sélectionnés comme remplaçant. Préparez-vous au transfert\nAppuyez sur F6 pour annuler"
			),
			'en' => array(
				'text' =>  "\$0F0You are selected to be a substitute. Prepare to be transferred\nPress F6 to cancel"
			),
		);
	}
}

?>

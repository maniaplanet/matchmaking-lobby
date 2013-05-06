<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\GUI;

use ManiaLivePlugins\MatchMakingLobby\Services\Match;

/**
 * Extend this if your are running a team mode script
 */
abstract class AbstractTeam extends AbstractGUI
{
	public $displayAllies = true;

	public function getLaunchMatchText(Match $m, $player)
	{
		$key = array_search($player, $m->team1);
		if($key !== false)
		{
			$mates = $this->getMates($m->team1, $player);
		}
		else
		{
			$mates = $this->getMates($m->team2, $player);
		}
		$mates = array_map(function ($player) { return sprintf('$<%s$>', $player); }, $mates);
		return array(
			'fr' => array(
				'text' =>  sprintf("\$0F0Votre match avec %s commence dans \$<\$FFF%%1 \$>...\nF6 pour annuler", implode(' & ', $mates))
			),
			'en' => array(
				'text' =>  sprintf("\$0F0Match with %s starts in \$<\$FFF%%1 \$>...\nF6 to cancel", implode(' & ', $mates))
			),
		);
	}

	protected function getMates(array $team, $player)
	{
		$mates = array();
		foreach ($team as $aPlayer)
		{
			if ($aPlayer == $player)
			{
				continue;
			}
			$playerObject = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($aPlayer);
			$mates[] = $playerObject ? $playerObject->nickName : $aPlayer;
		}
		return $mates;
	}
	
	function getCustomizedQuitDialogManiaLink()
	{
		$message = array(
			'fr' => array(
				'text' =>  'Voulez-vous vraiment abandonner vos alliés ?'
			),
			'en' => array(
				'text' =>  'Do you really want to abandon your teammates?'
			),
		);
		$manialink = new \ManiaLivePlugins\MatchMakingLobby\Views\CustomizedQuitDialog($message);
		return $manialink->display();
	}
}
?>
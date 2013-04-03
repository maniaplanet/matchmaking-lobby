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
		return sprintf('$0F0Match with %s starts in $<$FFF%%1 $>, F6 to cancel...', implode(' & ', $mates));
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
			$playerObject = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($player);
			$mates[] = $playerObject ? $playerObject->nickName : $player;
		}
		return $mates;
	}
}
?>
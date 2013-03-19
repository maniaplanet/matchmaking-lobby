<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

class LadderPointsDistance extends AbstractDistance
{
	protected function distributePlayers(\ManiaLivePlugins\MatchMakingLobby\Services\Match $match)
	{

	}

	protected function distance($p1, $p2)
	{
		$distance = abs($p1->ladderPoints - $p2->ladderPoints);

		// Waiting time coefficient
		$waitingTime = $p1->getWaitingTime() + $p2->getWaitingTime();
		$distance *= exp(-log(2) * $waitingTime / self::WAITING_STEP);

		return $distance;
	}

	public function getBackup($missingPlayer)
	{

	}
}
?>
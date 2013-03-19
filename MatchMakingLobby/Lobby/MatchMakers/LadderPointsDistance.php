<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

use \ManiaLivePlugins\MatchMakingLobby\Services\Match;
use ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo;
use ManiaLivePlugins\MatchMakingLobby\Services\MatchMakingService;

class LadderPointsDistance extends AbstractDistance
{
	protected function distributePlayers(Match $match)
	{

	}

	protected function distance(PlayerInfo $p1, PlayerInfo $p2)
	{
		$distance = abs($p1->ladderPoints - $p2->ladderPoints);

		// Waiting time coefficient
		$waitingTime = $p1->getWaitingTime() + $p2->getWaitingTime();
		$distance *= exp(-log(2) * $waitingTime / self::WAITING_STEP);

		return $distance;
	}

	public function getBackup($missingPlayer)
	{
		$readyPlayers = PlayerInfo::GetReady();
		$matchMakingService = new MatchMakingService();
		$readyPlayers = array_filter($readyPlayers,
			function ($readyPlayer) use($matchMakingService)
			{
				return !$matchMakingService->isInMatch($readyPlayer->login);
			});
		if($readyPlayers)
		{
			$quitterInfo = PlayerInfo::Get($missingPlayer);
			// Sort ready players to have the one with the same level
			usort($readyPlayers,
				function (PlayerInfo $p1, PlayerInfo $p2) use ($quitterInfo)
				{
					$dist1 = abs($quitterInfo->ladderPoints - $p1->ladderPoints);
					$dist2 = abs($quitterInfo->ladderPoints - $p2->ladderPoints);
					if($dist1 == $dist2)
					{
						return 0;
					}
					return ($dist1 < $dist2) ? -1 : 1;
				}
			);
			$player = array_shift($readyPlayers);
			return $player->login;
		}
		else
		{
			return false;
		}
	}
}
?>
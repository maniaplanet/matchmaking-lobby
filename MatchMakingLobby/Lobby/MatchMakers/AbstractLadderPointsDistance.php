<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo;

abstract class AbstractLadderPointsDistance extends AbstractDistance
{

	const MAX_DISTANCE_BACKUP = 10000;

	protected function dispatch($players)
	{
		$teams = array();

		$playersObject = array_map('\ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo::Get', $players);

		usort($playersObject, function ($a, $b)
		{
			return $a->ladderPoints - $b->ladderPoints;
		});

		$itemsToStack = 1;
		$currentIndex = 0;
		foreach($playersObject as $index => $player)
		{
			if ($itemsToStack > 0)
			{
				$itemsToStack--;
			}
			else
			{
				$itemsToStack = 1;
				$currentIndex = ($currentIndex == 0 ? 1 : 0);
			}
			$teams[$currentIndex][] = $player->login;
		}

		return $teams;
	}

	protected function playersDistance($p1, $p2)
	{
		$p1 = PlayerInfo::Get($p1);
		$p2 = PlayerInfo::Get($p2);
		$distance = abs($p1->ladderPoints - $p2->ladderPoints);

		// Waiting time coefficient
		$waitingTime = $p1->getWaitingTime() + $p2->getWaitingTime();
		$distance *= exp(-log(2) * $waitingTime / static::WAITING_STEP);

		return $distance;
	}

	protected function teamsDistance($t1, $t2)
	{
		$points = array();
		foreach(array($t1, $t2) as $index => $team)
		{
			$points[$index] = array_reduce($team, function ($result, $player) { return $result + PlayerInfo::Get($player)->ladderPoints/3; }, 0);
		}
		return abs($points[1] - $points[0]);
	}

	public function getBackup($missingPlayer, array $players = array())
	{
		if($players)
		{
			$playersObject = array_map('\ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo::Get', $players);
			$quitterInfo = PlayerInfo::Get($missingPlayer);
			// Sort ready players to have the one with the same level
			usort($playersObject,
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
			$player = array_shift($playersObject);

			//If distance is too big, no replacer
			if (abs($player->ladderPoints-$quitterInfo->ladderPoints) > static::MAX_DISTANCE_BACKUP)
			{
				return false;
			}

			return $player->login;
		}
		else
		{
			return false;
		}
	}

	public function findClosePlayer($closeTo, $availablePlayers, $number)
	{
		if ($number == 0 || count($availablePlayers) < $number)
		{
			return array();
		}

		$result = array();
		for($i=0; $i<$number; $i++)
		{
			$login = $this->getBackup(array_rand($closeTo), $availablePlayers);
			//We may not find a backup!
			if ($login)
			{
				$key = array_search($login, $availablePlayers);
				if ($key !== false)
				{
					unset($availablePlayers[$key]);
					$result[] = $login;
				}
			}
		}
		if (count($result) != $number)
		{
			return array();
		}
		return $result;
	}
}
?>
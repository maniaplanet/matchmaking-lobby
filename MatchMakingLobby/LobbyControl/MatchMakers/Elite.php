<?php

/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\LobbyControl\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\LobbyControl\Match;
use ManiaLivePlugins\MatchMakingLobby\LobbyControl\PlayerInfo;

class Elite extends AbstractMatchMaker
{
	protected $isTeamMode = true;
	
	const DISTANCE_THRESHOLD = 1000;

	public function getPlayerScore($login)
	{
		return \ManiaLive\Data\Storage::getInstance()->getPlayerObject($login)->ladderStats['PlayerRankings'][0]['Score'];
	}

	function run()
	{
		$matches = parent::run(6);

		$matches = array_map(array($this, 'distributePlayers'), $matches);

		return $matches;
	}

	/**
	 * @param PlayerInfo $p1
	 * @param PlayerInfo $p2
	 */
	protected function distance($p1, $p2)
	{
		// If players are allies there is no distance between them
		$playerObj = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($p1->login);
		if(in_array($p2->login, $playerObj->allies))
		{
			return 0;
		}
		
		return parent::distance($p1, $p2);
	}

	protected function distributePlayers(Match $m)
	{
		$players = $m->players;
		usort($players,
			function ($a, $b)
			{
				$pa = PlayerInfo::Get($a);
				$pb = PlayerInfo::Get($b);
				if($pa->ladderPoints == $pb->ladderPoints)
				{
					return 0;
				}
				return ($pa->ladderPoints > $pb->ladderPoints) ? -1 : 1;
			}
		);

		$teamNumber = false;
		foreach($players as $key => $player)
		{
			/* @var $player \DedicatedApi\Structures\Player */
			$m->addPlayerInTeam($player, $teamNumber);
			if($key % 2 == 0) $teamNumber = !$teamNumber;
		}
		
		$player = reset($m->team1);
		do
		{
			$playerObj = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($player);
		}
		while(!$playerObj->allies && $player = next($m->team1));
		
		if($playerObj->allies)
		{
			$alliesKeys = array_keys($m->team2, $playerObj->allies);
			foreach($m->team1 as $key => $teammate)
			{
				if($teammate == $player)
				{
					continue;
				}
				$tmp = $m->team2[current($alliesKeys)];
				$m->team2[current($alliesKeys)] = $teammate;
				$m->team1[$key] = $tmp;
				if(!next($alliesKeys))
				{
					break;
				}
			}
		}
		else
		{
			$player = reset($m->team2);
			do
			{
				$playerObj = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($player);
			}
			while(!$playerObj->allies && $player = next($m->team2));
			if($playerObj->allies)
			{
				$alliesKeys = array_keys($m->team1, $playerObj->allies);
				foreach($m->team2 as $key => $teammate)
				{
					if($teammate == $player)
					{
						continue;
					}
					$tmp = $m->team1[current($alliesKeys)];
					$m->team2[current($alliesKeys)] = $teammate;
					$m->team1[$key] = $tmp;
					if(!next($alliesKeys))
					{
						break;
					}
				}
			}
		}
		
		$m->team1 = array_unique($m->team1);
		$m->team2 = array_unique($m->team2);

		return $m;
	}
}

?>
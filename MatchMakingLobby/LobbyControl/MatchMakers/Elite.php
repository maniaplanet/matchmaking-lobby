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
use ManiaLivePlugins\MatchMakingLobby\LobbyControl\Helpers;

class Elite extends AbstractMatchMaker
{
	
	const DISTANCE_THRESHOLD = 500;

	public function getPlayerScore($login)
	{
		return \ManiaLive\Data\Storage::getInstance()->getPlayerObject($login)->ladderStats['PlayerRankings'][0]['Score'];
	}

	function run()
	{
		$matches = parent::run(6);
		
			if(Config::getInstance()->isTeamMode)
			{
				$matches = array_map(array($this, 'distributePlayers'), $matches);
			}

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
		return $m;
	}
}

?>
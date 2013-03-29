<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo;

class DistanceElite extends AbstractLadderPointsDistance
{
	const DISTANCE_THRESHOLD = 2000;

	function getNumberOfTeam()
	{
		return 2;
	}

	function getPlayersPerMatch()
	{
		return 6;
	}

	/**
	 * @param PlayerInfo $p1
	 * @param PlayerInfo $p2
	 */
	protected function distance($p1, $p2)
	{
		$p1Obj = PlayerInfo::Get($p1);
		$p2Obj = PlayerInfo::Get($p2);

		// If players are allies there is no distance between them
		if(in_array($p2Obj->login, $p1Obj->allies))
		{
			return -1;
		}
		return parent::distance($p1, $p2);
	}
}

?>
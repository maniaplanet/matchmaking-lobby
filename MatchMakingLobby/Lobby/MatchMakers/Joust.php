<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

class Joust extends LadderPointsDistance
{

	public $playerPerMatch = 2;

	const DISTANCE_THRESHOLD = 1000;

	public function getBackup($missingPlayer, array $players = array())
	{
		return false;
	}
}

?>
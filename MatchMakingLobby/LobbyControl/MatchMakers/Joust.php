<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\LobbyControl\MatchMakers;

class Joust extends AbstractMatchMaker
{

	public $playerPerMatch = 2;

	const DISTANCE_THRESHOLD = 1000;

	protected function distance($p1, $p2)
	{
		return parent::distance($p1, $p2);
	}

	public function getPlayerScore($login)
	{
		return \ManiaLive\Data\Storage::getInstance()->getPlayerObject($login)->ladderStats['PlayerRankings'][0]['Score'];
	}

}

?>
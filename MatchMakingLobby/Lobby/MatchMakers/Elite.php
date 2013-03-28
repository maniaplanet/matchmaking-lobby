<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\Services\Match;
use ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo;

class Elite extends AbstractAllies
{
	public function getBackup($missingPlayer, array $players = array())
	{
		return $this->getFallbackMatchMaker()->getBackup($missingPlayer, $players);
	}

	function getNumberOfTeam()
	{
		return 2;
	}

	function getPlayersPerMatch()
	{
		return 6;
	}

	protected function getFallbackMatchMaker()
	{
		return DistanceElite::getInstance();
	}

}
?>
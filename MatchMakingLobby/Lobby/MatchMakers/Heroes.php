<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

class Heroes extends AbstractAllies
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
		return 10;
	}

	protected function getFallbackMatchMaker()
	{
		return DistanceHeroes::getInstance();
	}

}
?>
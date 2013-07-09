<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

class ElitePractice extends AbstractLadderPointsDistance
{
	function getNumberOfTeam()
	{
		return 0;
	}

	function getPlayersPerMatch()
	{
		return 4;
	}
}
?>
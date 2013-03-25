<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\Services\Match;

/**
 * Implement this to create your own match maker.
 */
interface MatchMakerInterface
{
	/**
	 * @param array $players Login of players available for MatchMaking
	 * @return Match[]
	 */
	public function run(array $players = array());

	public function getTeams(array $players = array());

	public function getMatches(array $teams = array());

	/**
	 * @param string $missingPlayer Login of player
	 * @return string Login of player that can replace the missing player or false if no player found
	 */
	public function getBackup($missingPlayer, array $players = array());

	/**
	 * @return int Number of player per match
	 */
	public function getPlayersPerMatch();

	/**
	 * @return int Number of teams
	 */
	public function getNumberOfTeam();

}
?>
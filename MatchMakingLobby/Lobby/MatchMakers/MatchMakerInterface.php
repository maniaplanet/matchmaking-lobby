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
	 * Mail function called by Lobby
	 * It usually use getTeams to get a list of teams from available players
	 * and then getMatches on theses teams to return the matchs.
	 * @param array $players Login of players available for MatchMaking
	 * @return Match[]
	 */
	public function run(array $players = array());

	/**
	 * This function is usually not used from the outside world
	 * We are using it in order to share functionalities between match makers
	 * @param array $players
	 */
	public function getTeams(array $players = array());

	/**
	 * This function is usually not used from the outside world
	 * We are using it in order to share functionalities between match makers
	 * @param array $teams
	 */
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
	 * @return int Number of teams per match. 0 for non team mode
	 */
	public function getNumberOfTeam();

}
?>
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

	/**
	 * @param string $missingPlayer Login of player
	 * @return string Login of player that can replace the missing player or false if no player found
	 */
	public function getBackup($missingPlayer);
}
?>
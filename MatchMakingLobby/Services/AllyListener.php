<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Services;

interface AllyListener extends \ManiaLive\Event\Listener
{
	/**
	 * Method called when the ally list of the player change
	 * @param string $login
	 */
	function onAlliesChanged($login);
}

?>

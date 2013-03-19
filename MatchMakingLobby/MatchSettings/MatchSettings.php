<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\MatchSettings;

/**
 * Implement this to customize the MatchSettings used on Lobby and Server
 * You can define the MatchSettings class used by your lobby in :
 * \ManiaLivePlugins\MatchMakingLobby\Config->matchSettingsClassName
 */
interface MatchSettings
{
	/**
	 * Settings applied on the lobby server
	 * Must be an associative array : ruleName => value
	 * @return array
	 */
	function getLobbyScriptSettings();

	/**
	 * Settings applied on the match server
	 * Must be an associative array : ruleName => value
	 * @return array
	 */
	function getMatchScriptSettings();
}

?>

<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\MatchSettings;

class Elite implements MatchSettings
{
	public function getLobbyScriptSettings()
	{
		$rules = array(
			'S_UseLobby' => true
		);
		return $rules;
	}

	public function getMatchScriptSettings()
	{
		$rules = array(
			'S_UseLobby' => false,
			'S_UsePlayerClublinks' => true,
			'S_Mode' => 1,
			'S_MatchmakingSleep' => 15,
			'S_WarmUpDuration' => 5,
			'S_UseScriptCallbacks' => true,
			'S_Matchmaking' => true
		);
		return $rules;
	}
}

?>
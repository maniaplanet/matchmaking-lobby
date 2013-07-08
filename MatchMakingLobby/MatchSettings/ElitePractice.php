<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\MatchSettings;

class ElitePractice extends Elite implements MatchSettings
{
	public function getMatchScriptSettings()
	{
		$rules = parent::getMatchScriptSettings();
		$rules['S_Practice'] = true;
		$rules['S_PracticeRoundLimit'] = 3; //# of attack rounds per persons
		return $rules;
	}

}

?>
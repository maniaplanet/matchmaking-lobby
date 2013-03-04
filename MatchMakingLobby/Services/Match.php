<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Services;

class Match
{

	public $players = array();
	public $team1 = array();
	public $team2 = array();

	function addPlayerInTeam($login, $teamId)
	{
		switch($teamId)
		{
			case 0:
				if(array_search($login, $this->team2))
				{
					throw new \Exception();
				}
				if(!array_search($login, $this->team1))
				{
					$this->team1[] = $login;
				}
				break;
			case 1:
				if(array_search($login, $this->team1))
				{
					throw new \Exception();
				}
				if(!array_search($login, $this->team2))
				{
					$this->team2[] = $login;
				}
				break;
			default:
				throw new \InvalidArgumentException();
		}
	}

}

?>
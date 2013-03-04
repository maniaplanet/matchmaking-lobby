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

	/**
	 * List of all logins playing the match
	 * @var string[]
	 */
	public $players = array();
	/**
	 * Logins of players in team 1
	 * @var string[] 
	 */
	public $team1 = array();
	/**
	 * Same as team1
	 * @var string[]
	 */
	public $team2 = array();

	/**
	 * add a login in a team, throw an exception if the login is already teamed or if the team does not exist
	 * @param string $login
	 * @param int $teamId
	 * @throws \InvalidArgumentException
	 */
	function addPlayerInTeam($login, $teamId)
	{
		switch($teamId)
		{
			case 0:
				if(array_search($login, $this->team2))
				{
					throw new \InvalidArgumentException();
				}
				if(!array_search($login, $this->team1))
				{
					$this->team1[] = $login;
				}
				break;
			case 1:
				if(array_search($login, $this->team1))
				{
					throw new \InvalidArgumentException();
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
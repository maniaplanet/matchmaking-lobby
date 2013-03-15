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
	const PLAYER_GAVE_UP = -5;
	const PLAYER_LEFT = -4;
	const PLAYER_CANCEL = -3;
	const FINISHED = -2;
	const PREPARED = -1;
	const PLAYING = 1;
	const WAITING_REPLACEMENT = 2;
	
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

	/**
	 * @param type $login
	 * @return int 0|1|2
	 */
	function getTeam($login)
	{
		if ($this->isInTeam1($login))
			return 1;
		else if ($this->isInTeam2($login))
			return 2;
		else
			return 0;
	}

	function isInTeam1($login)
	{
		return $this->isInTeam($login, $this->team1);
	}

	function isInTeam2($login)
	{
		return $this->isInTeam($login, $this->team2);
	}

	protected function isInTeam($login, $team)
	{
		return in_array($login, $team);
	}

}

?>
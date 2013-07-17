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
	const FINISHED_WAITING_BACKUPS = -5;
	const PLAYER_LEFT = -4;
	const PLAYER_CANCEL = -3;
	const FINISHED = -2;
	const PREPARED = -1;
	const PLAYING = 1;
	const WAITING_BACKUPS = 2;

	/**
	 * String to use in a maniaplanet link to switch players on the lobby
	 * @var string
	 */
	public $id;

	/**
	 * The server login where the match is played
	 * @var string
	 */
	public $matchServerLogin;

	/**
	 * The name of the script use to play the match
	 * @var string
	 */
	public $scriptName;

	/**
	 * The titleIdString of the title where the match will be played
	 * @var string
	 */
	public $titleIdString;

	/**
	 *
	 * @var int
	 */
	public $state;

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
	 * Id of the player or the team who won the match
	 * @var int
	 */
	public $ranking;
	
	/**
	 * Number of map won by the team 1
	 * @var int
	 */
	public $matchPointsTeam1;
	
	/**
	 * Number of map won by the team 2
	 * @var int
	 */
	public $matchPointsTeam2;
	
	/**
	 * Number of point of the team 1 during the last map
	 * @var int
	 */
	public $mapPointsTeam1;
	
	/**
	 * Number of point of the team 2 during the last map
	 * @var int
	 */
	public $mapPointsTeam2;
	
	/**
	 * Current player state of the player
	 * @var int[string]
	 */
	public $playersState = array();

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

	function isDifferent(Match $m)
	{
		if($m->id != $this->id)
		{
			return false;
		}
		else
		{
			return ($m->players != $this->players);
		}
	}

	protected function isInTeam($login, $team)
	{
		return in_array($login, $team);
	}

}

?>
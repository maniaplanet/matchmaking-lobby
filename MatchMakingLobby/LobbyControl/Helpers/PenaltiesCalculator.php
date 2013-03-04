<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\LobbyControl\Helpers;

use ManiaLivePlugins\MatchMakingLobby\Config;

class PenaltiesCalculator
{
	private $penaltyTime;
	
	final function __construct()
	{
		$this->penaltyTime = Config::getInstance()->penaltyTime;
	}
	
	final function getPenaltyTime()
	{
		$this->penaltyTime;
	}

	/**
	 * Calculate Karma for the player and the number of time he left games
	 * 1 point of Karma = 1 leave by default
	 * @param string $login
	 * @param int $leavesCount
	 * @return int
	 */
	function calculateKarma($login, $leavesCount)
	{
		return $leavesCount;
	}
	
	/**
	 * Calculate the number of minutes of penalty for the players Karma
	 * @param string $login
	 * @param int $karma
	 * @return float
	 */
	function getPenalty($login, $karma)
	{
		return $this->penaltyTime <= 1 ? $this->penaltyTime * $karma : pow($this->penaltyTime, $karma);
	}

}

?>
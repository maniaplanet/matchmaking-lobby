<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Services;

class MatchInfo
{
	/**
	 * String to use in a maniaplanet link to switch players on the lobby
	 * @var string
	 */
	public $backLink;
	/**
	 * Login of the lobby server
	 * @var string
	 */
	public $lobby;
	/**
	 * The Match itself
	 * @var Match
	 */
	public $match;
	
	function __construct()
	{
		if($this->match)
		{
			$tmp = json_decode($this->match);
			$this->match = new Match();
			$this->match->players = $tmp->players;
			$this->match->team1 = $tmp->team1;
			$this->match->team2 = $tmp->team2;
		}
	}
}

?>
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
	public $matchId;
	
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
	 * The Match itself
	 * @var Match
	 */
	public $match;
}

?>
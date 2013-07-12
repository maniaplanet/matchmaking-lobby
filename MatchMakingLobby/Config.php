<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby;

class Config extends \ManiaLib\Utils\Singleton
{
	const REQUIRED_MANIALIVE = '3.1.0';

	/**
	 * Login of your lobby
	 * You do not have to set up the login of your match servers anywhere
	 * All match server should only set the same lobby login
	 * @var string
	 */
	public $lobbyLogin;

	/**
	 * Script name use by lobby
	 * If not set it will be guessed from the server.
	 * This value is used for default values of many
	 * @var string
	 */
	public $script;

	/**
	 * Every time a player quits, he will be banned by :
	 * penaltyTime^(number of leaves)
	 * @var int
	 */
	public $penaltyTime = 2;

	/**
	 * Depending of the value, the match plugin will wait for backups during some time before aborting the match,
	 * or, will wait backups until the end of the match, or don't wait at all
	 * 0 to not wait backups
	 * 1 to wait before aborting the match
	 * 2 to wait until the end of the match
	 * @var int
	 */
	public $waitingForBackups = 2;

	/**
	 * If the team reach the minimum number of player, the match is cancelled
	 * @var int
	 */
	public $minPlayersByTeam = 0;

	/**
	 * Name of the class used by the match maker.
	 * Default value is :  \ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers\<ScriptName as defined in config>
	 * @var string Complete class name (with namespace)
	 */
	public $matchMakerClassName;

	/**
	 * Name of the class used for the GUI
	 * Default \ManiaLivePlugins\MatchMakingLobby\GUI\<ScriptName as defined in config>
	 * @var string Complete class name (with namespace)
	 */
	public $guiClassName;

	/**
	 * Name of the class used for setting the needed match settings
	 * Default \ManiaLivePlugins\MatchMakingLobby\MatchSettings\<ScriptName as defined in config>
	 * @var string Complete class name (with namespace)
	 */
	public $matchSettingsClassName;


	/**
	 * Duration in second of the penalty for leaving a match
	 * @var int
	 */
	public $penaltyForQuitter = 150;

	/**
	 * @var int
	 */
	public $authorizedMatchCancellation = 0;

	/**
	 * If null, scriptName will be used
	 * @var string
	 */
	public $dictionary;

	/**
	 * If defined the logo will be clickable and will redirect the player to this link
	 * @var string
	 */
	public $logoLink;

	/**
	 * If defined a logo will be displayed when the player is waiting
	 * @var string
	 */
	public $logoURL;

	/**
	 * Run matchmaker automatically every N seconds.
	 * If set to 0, will wait for XML-RPC event 'RunMatchMaker' to run it
	 * @var int
	 */
	public $matchMakerDelay = 0;

	public function getMatchSettingsClassName($scriptName)
	{
		return $this->matchSettingsClassName ? : '\ManiaLivePlugins\MatchMakingLobby\MatchSettings\\'.$scriptName;
	}

	public function getMatchMakerClassName($scriptName)
	{
		return $this->matchMakerClassName ? : '\ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers\\'.$scriptName;
	}

	public function getGuiClassName($scriptName)
	{
		return $this->guiClassName ? : '\ManiaLivePlugins\MatchMakingLobby\GUI\\'.$scriptName;
	}

	public function getDictionnary($scriptName)
	{
		return $this->dictionary ? : $scriptName;
	}

}

?>

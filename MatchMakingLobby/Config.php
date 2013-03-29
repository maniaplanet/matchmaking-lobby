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
	public $penaltyTime = 4;

	/**
	 * Depending of the value, the match plugin will wait for backups during some time before aborting the match,
	 * or, will wait backups until the end of the match, or don't wait at all
	 * 0 to not wait backups
	 * 1 to wait before aborting the match
	 * 2 to wait until the end of the match
	 * @var int
	 */
	public $waitingForBackups = 1;

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
	 * Name of the class used to calculate the penalties
	 * Default is \ManiaLivePlugins\MatchMakingLobby\Helpers\PenaltiesCalculator
	 * @var string
	 */
	public $penaltiesCalculatorClassName;

	/**
	 * Name of the class used for setting the needed match settings
	 * Default \ManiaLivePlugins\MatchMakingLobby\MatchSettings\<ScriptName as defined in config>
	 * @var string Complete class name (with namespace)
	 */
	public $matchSettingsClassName;
}

?>

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
	 * @var string
	 */
	public $lobbyLogin;

	/**
	 * Script name use by lobby
	 * If not set it will be guessed from the server
	 * @var type
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

	public $minPlayersByTeam = 1;

	public $matchMakerClassName;
	public $guiClassName;
	public $penaltiesCalculatorClassName;
	public $penaltyClass;

	public $matchSettingsClassName;
}

?>

<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Services;

use ManiaLive\Database\MySQL\Connection;

class LobbyService
{

	/** @var string */
	protected $modeClause;

	/** @var \ManiaLive\Database\MySQL\Connection */
	protected $db;

	function __construct($titleIdString, $scriptName = '')
	{
		$config = \ManiaLive\Database\Config::getInstance();
		$this->db = Connection::getConnection(
				$config->host, $config->username, $config->password, $config->database, $config->type, $config->port
		);

		$this->modeClause = sprintf('title=%s', $this->db->quote($titleIdString));
		if(strpos($titleIdString, '@') === false)
				$this->modeClause .= sprintf(' AND script=%s', $this->db->quote($scriptName));
	}

	/**
	 * Get lobby information
	 * @param string $lobbyLogin
	 * @return \stdClass
	 */
	function get($lobbyLogin)
	{
		return $this->db->execute(
				'SELECT * FROM Lobbies '.
				'WHERE login = %s', $this->db->quote($lobbyLogin)
			)->fetchObject();
	}

	/**
	 * Get the number of server the lobby can use
	 * @param string $lobbyLogin
	 * @return int
	 */
	function getServersCount($lobbyLogin)
	{
		return $this->db->execute(
				'SELECT COUNT(*) FROM Servers '.
				'WHERE (DATE_ADD(lastLive, INTERVAL 20 SECOND) > NOW() AND %s) OR '.
				'lobby = %s', $this->modeClause, $this->db->quote($lobbyLogin)
			)->fetchSingleValue(0);
	}

	/**
	 * Register a lobby server in the system
	 * @param string $lobbyLogin
	 * @param int $readyPlayersCount
	 * @param int $connectedPlayersCount
	 * @param int $playingPlayersCount
	 * @param string $serverName
	 * @param string $backLink
	 */
	function register($lobbyLogin, $readyPlayersCount, $connectedPlayersCount, $playingPlayersCount, $serverName, $backLink)
	{
		$this->db->execute(
			'INSERT INTO Lobbies VALUES (%s, %d, %d, %d, %s, %s) '.
			'ON DUPLICATE KEY UPDATE '.
			'readyPlayers = VALUES(readyPlayers), '.
			'connectedPlayers = VALUES(connectedPlayers), '.
			'playingPlayers = VALUES(playingPlayers)', $this->db->quote($lobbyLogin), $readyPlayersCount, $connectedPlayersCount,
			$playingPlayersCount, $this->db->quote($serverName), $this->db->quote($backLink)
		);
	}

}

?>
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

class MatchService
{
	/**
	 * @var Connection
	 */
	protected $db;
	
	/**
	 * @var string
	 */
	protected $modeClause;
	
	function __construct($titleIdString, $scriptName = '')
	{
		$config = \ManiaLive\Database\Config::getInstance();
		$this->db = Connection::getConnection(
				$config->host,
				$config->username,
				$config->password,
				$config->database,
				$config->type,
				$config->port
		);
		
		$this->modeClause = sprintf('title=%s', $this->db->quote($titleIdString));
		if(strpos($titleIdString, '@') === false)
				$this->modeClause .= sprintf(' AND script=%s', $this->db->quote($scriptName));
	}
	
	/**
	 * Get all information for the matchServer
	 * @param string $serverLogin
	 * @return \stdClass
	 */
	function get($serverLogin)
	{
		return $this->db->execute(
				'SELECT H.backLink, S.lobby, S.players as `match` FROM Servers  S '.
				'INNER JOIN Lobbies H ON S.lobby = H.login '.
				'WHERE S.login=%s', $this->db->quote($serverLogin)
			)->fetchObject('\ManiaLivePlugins\MatchMakingLobby\Services\MatchInfo');
	}
	
	/**
	 * Return the number of match currently played for the lobby
	 * @param string $lobbyLogin
	 * @return int
	 */
	function getCount($lobbyLogin)
	{
		return $this->db->execute(
				'SELECT COUNT(*) FROM Servers '.
				'WHERE '.$this->modeClause.' AND lobby = %s', $this->db->quote($lobbyLogin)
			)->fetchSingleValue(0);
	}
	
	/**
	 * Get a server available to host a match
	 * @return string the match server login
	 */
	function getServer()
	{
		return $this->db->execute(
				'SELECT login FROM Servers '.
				'WHERE '.$this->modeClause.' AND lobby IS NULL AND DATE_ADD(lastLive, INTERVAL 20 SECOND) > NOW() '.
				'ORDER BY RAND() LIMIT 1'
			)->fetchSingleValue(null);
	}
	
	/**
	 * Check if the player is in Match and the match is still playing
	 * @param string $login
	 * @return boolean
	 */
	function isInMatch($login)
	{
		list($server, $players) = PlayerInfo::Get($login)->getMatch();
		if(!$server || !$players) return false;
		return $this->db->execute(
				'SELECT IF(count(*), TRUE, FALSE) FROM Servers '.
				'WHERE login = %s and players = %s', $this->db->quote($server), $this->db->quote(json_encode($players))
			)->fetchSingleValue(false);
	}
	
	/**
	 * Remove a match from a server
	 * @param string $serverLogin
	 */
	function removeMatch($serverLogin)
	{
		$this->db->execute(
			'UPDATE Servers SET lobby=NULL, players=NULL WHERE login=%s', $this->db->quote($serverLogin)
		);
	}
	
	/**
	 * Register a match in database, the match Server will use this to ready up
	 * @param string $lobbyLogin
	 * @param string $serverLogin
	 * @param \ManiaLivePlugins\MatchMakingLobby\Services\Match $match
	 */
	function registerMatch($lobbyLogin, $serverLogin, Match $match)
	{
		$this->db->execute(
			'UPDATE Servers SET lobby=%s, players=%s WHERE login=%s', $this->db->quote($lobbyLogin),
			$this->db->quote(json_encode($match)), $this->db->quote($serverLogin)
		);
	}
	
	/**
	 * Register a server as match server
	 * @param string $serverLogin
	 * @param string $titleId
	 * @param string $script
	 */
	function registerServer($serverLogin, $titleId, $script)
	{
		$this->db->execute(
			'INSERT INTO Servers(login, title, script, lastLive) VALUES(%s, %s, %s, NOW()) '.
			'ON DUPLICATE KEY UPDATE title=VALUES(title), script=VALUES(script), lastLive=VALUES(lastLive)',
			$this->db->quote($serverLogin), 
			$this->db->quote($titleId),
			$this->db->quote($script)
		);
	}
}

?>
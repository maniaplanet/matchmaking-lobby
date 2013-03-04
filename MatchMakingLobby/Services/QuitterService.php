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

class QuitterService
{
	/** @var string */
	protected $lobbyLogin;

	/** @var \ManiaLive\Database\MySQL\Connection */
	protected $db;

	function __construct($lobbyLogin)
	{
		$config = \ManiaLive\Database\Config::getInstance();
		$this->db = Connection::getConnection(
				$config->host, $config->username, $config->password, $config->database, $config->type, $config->port
		);

		$this->lobbyLogin = $lobbyLogin;
	}
	
	/**
	 * Register a player as a quitter
	 * @param string $playerLogin
	 */
	function register($playerLogin)
	{
		$this->db->execute(
			'INSERT INTO Quitters VALUES (%s,NOW(), %s)', 
			$this->db->quote($playerLogin), 
			$this->db->quote($this->lobbyLogin)
		);
	}
	
	/**
	 * Get the number of time the player quit a match
	 * @param string $playerLogin
	 * @return int
	 */
	function getCount($playerLogin)
	{
		return $this->db->query(
				'SELECT count(*) FROM Quitters '.
				'WHERE playerLogin = %s '.
				'AND lobby = %s '.
				'AND DATE_ADD(creationDate, INTERVAL 1 HOUR) > NOW()', $this->db->quote($playerLogin),
				$this->db->quote($this->lobbyLogin)
			)->fetchSingleValue();
	}
	
//	function clean()
//	{
//		$this->db->execute('DELETE FROM Quitters WHERE DATE_ADD(creationDate, INTERVAL 1 HOUR) < NOW()');
//	}
}

?>
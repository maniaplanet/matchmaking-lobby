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

class MatchMakingService
{
	/**
	 * @var Connection
	 */
	protected $db;

	function __construct()
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

	}

	/**
	 * Get lobby information
	 * @param string $lobbyLogin
	 * @return Lobby
	 */
	function getLobby($lobbyLogin)
	{
		return $this->db->execute(
				'SELECT * FROM LobbyServers '.
				'WHERE login = %s', $this->db->quote($lobbyLogin)
			)->fetchObject(__NAMESPACE__.'\Lobby');
	}

	/**
	 * Get matchServer information
	 * @param string $serverLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return Match
	 */
	function getMatch($matchId)
	{
		$match = $this->db->execute(
				'SELECT id, matchServerLogin, scriptName, titleIdString, state '.
				'FROM Matches '.
				'WHERE id = %d ',
				$matchId
			)->fetchObject(__NAMESPACE__.'\\Match');
		if(!$match)
			return false;

		$results = $this->db->execute(
				'SELECT P.login, P.teamId '.
				'FROM Players P '.
				'WHERE P.matchId = %d ', $match->id
			)->fetchArrayOfAssoc();

		foreach($results as $row)
		{
			$match->players[] = $row['login'];
			if($row['teamId'] === null)
			{
				continue;
			}
			elseif((int)$row['teamId'] === 0)
			{
				$match->team1[] = $row['login'];
			}
			elseif((int)$row['teamId'] === 1)
			{
				$match->team2[] = $row['login'];
			}
		}

		return $match;
	}

	/**
	 * @param int[] $matchIds
	 * @return Match[]
	 */
	function getMatches(array $matchIds)
	{
		$result = array();
		foreach($matchIds as $matchId)
		{
			$result[] = $this->getMatch($matchId);
		}
		return $result;
	}

	/**
	 *
	 * @param string $serverLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return string
	 */
	function getServerCurrentMatchId($serverLogin, $scriptName, $titleIdString)
	{
		return $this->db->execute(
				'SELECT matchId FROM MatchServers '.
				'WHERE login = %s  AND scriptName = %s AND titleIdString = %s ',
				$this->db->quote($serverLogin),
				$this->db->quote($scriptName),
				$this->db->quote($titleIdString)
			)->fetchSingleValue();
	}

	/**
	 * @param string $serverLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return Match
	 */
	function getServerCurrentMatch($serverLogin, $scriptName, $titleIdString)
	{
		return $this->getMatch($this->getServerCurrentMatchId($serverLogin, $scriptName, $titleIdString));
	}

	/**
	 * @param string $serverLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return Match
	 */
	function getCurrentLobbyMatches($lobbyLogin, $scriptName, $titleIdString)
	{
		return $this->getMatches($this->getCurrentLobbyMatchIds($lobbyLogin, $scriptName, $titleIdString));
	}

	/**
	 * @param string $lobbyLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return string
	 */
	function getCurrentLobbyMatchIds($lobbyLogin, $scriptName, $titleIdString)
	{
		return $this->db->execute(
				'SELECT matchId FROM MatchServers '.
				'WHERE lobbyLogin = %s  AND scriptName = %s AND titleIdString = %s '.
				'AND matchId IS NOT NULL',
				$this->db->quote($lobbyLogin),
				$this->db->quote($scriptName),
				$this->db->quote($titleIdString)
				)->fetchArrayOfSingleValues();
	}

	function cancelMatch(Match $match)
	{
		$this->updateMatchState($match->id, Match::PLAYER_CANCEL);

		$this->updateServerCurrentMatchId(null, $match->matchServerLogin, $match->scriptName, $match->titleIdString);
	}

	function getMatchesNeedingBackup($lobbyLogin, $scriptName, $titleIdString)
	{
		$ids = $this->db->execute(
				'SELECT M.id FROM Matches M '.
				'INNER JOIN MatchServers MS ON M.id = MS.matchId '.
				'WHERE MS.lobbyLogin = %s  AND M.scriptName = %s AND M.titleIdString = %s '.
				'AND M.state = %d ',
				$this->db->quote($lobbyLogin),
				$this->db->quote($scriptName),
				$this->db->quote($titleIdString),
				Match::WAITING_BACKUPS
			)->fetchArrayOfSingleValues();

		return array_map(array($this,'getMatch'), $ids);
	}

	/**
	 * Get login of players who have quit the match
         * (quitters or gave up)
	 * @param int $matchId
	 * @return string[]
	 */
	function getMatchQuitters($matchId)
	{
		return $this->db->execute('SELECT login FROM Players WHERE matchId = %d AND (`state` = %d OR `state` = %d)',
                        $matchId,
                        PlayerInfo::PLAYER_STATE_QUITTER,
                        PlayerInfo::PLAYER_STATE_GIVE_UP)->fetchArrayOfSingleValues();
	}

	function getPlayersJustFinishedAllMatches($lobbyLogin, array $guest)
	{
		return $this->db->execute(
				'SELECT P.login '.
				'FROM Players P '.
				'INNER JOIN Matches M ON P.matchId = M.id '.
				'WHERE DATE_ADD(M.lastUpdateDate, INTERVAL 30 SECOND) < NOW() AND DATE_ADD(M.lastUpdateDate, INTERVAL 90 SECOND) > NOW() AND M.state <= %d '.
				'AND M.lobbyLogin = %s '.(count($guest) ? 'AND P.login IN (%s) ' : '').
				'AND P.login NOT IN (SELECT login FROM Players WHERE matchId > M.id)',
				Match::FINISHED, $this->db->quote($lobbyLogin), implode(',',array_map(array($this->db, 'quote'), $guest))
		)->fetchArrayOfSingleValues();
	}

	/**
	 * Return the number of match currently played for the lobby
	 * @param string $lobbyLogin
	 * @return int
	 */
	function getPlayersPlayingCount($lobbyLogin)
	{
		return $this->db->execute(
				'SELECT COUNT(*) '.
				'FROM Players P '.
				'INNER JOIN Matches M ON P.matchId = M.id '.
				'INNER JOIN MatchServers MS ON MS.matchId = M.id '.
				'WHERE M.`state` >= %d AND P.state >= %d '.
				'AND MS.lobbyLogin = %s',
				Match::PREPARED, PlayerInfo::PLAYER_STATE_CONNECTED,
				$this->db->quote($lobbyLogin)
			)->fetchSingleValue(0);
	}

	/**
	 * Get the number of server the lobby can use
	 * @param string $lobbyLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return int
	 */
	function getLiveMatchServersCount($lobbyLogin, $scriptName, $titleIdString)
	{
		return $this->db->execute(
				'SELECT COUNT(*) FROM MatchServers '.
				'WHERE DATE_ADD(lastLive, INTERVAL 15 MINUTE) > NOW() '.
				'AND lobbyLogin = %s AND scriptName = %s AND titleIdString = %s',
				$this->db->quote($lobbyLogin), $this->db->quote($scriptName), $this->db->quote($titleIdString)
			)->fetchSingleValue(0);
	}

	/**
	 * Get the number of time the player quit a match for this lobby
	 * @param string $playerLogin
	 * @return int
	 */
	function getLeaveCount($playerLogin, $lobbyLogin)
	{
		return $this->db->query(
				'SELECT count(*) FROM Players P '.
				'INNER JOIN Matches M ON P.matchId = M.id '.
				'WHERE P.login = %s AND P.`state` IN (%s) AND M.lobbyLogin = %s '.
				'AND M.state NOT IN (%s) '.
				'AND DATE_ADD(M.creationDate, INTERVAL 1 HOUR) > NOW()',
				$this->db->quote($playerLogin),
				implode(',', array(PlayerInfo::PLAYER_STATE_NOT_CONNECTED, PlayerInfo::PLAYER_STATE_QUITTER, PlayerInfo::PLAYER_STATE_GIVE_UP, PlayerInfo::PLAYER_STATE_REPLACED)),
				implode(',', array(Match::PLAYER_CANCEL)),
				$this->db->quote($lobbyLogin)
			)->fetchSingleValue(0);
	}

	/**
	 * Get number of server available to host a match
	 * for the lobby
	 * @param string $lobbyLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return string the match server login
	 */
	function countAvailableServer($lobbyLogin, $scriptName, $titleIdString)
	{
		return $this->db->execute(
				'SELECT count(MS.login) FROM MatchServers MS '.
				'WHERE MS.lobbyLogin = %s AND MS.scriptName = %s AND MS.titleIdString = %s '.
				'AND MS.`state` = %d AND matchId IS NULL '.
				'AND DATE_ADD(MS.lastLive, INTERVAL 20 SECOND) > NOW() ',
				$this->db->quote($lobbyLogin), $this->db->quote($scriptName), $this->db->quote($titleIdString),
				\ManiaLivePlugins\MatchMakingLobby\Match\Plugin::SLEEPING
			)->fetchSingleValue(null);
	}

	/**
	 * Get a server available to host a match
	 * for the lobby
	 * @param string $lobbyLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return string the match server login
	 */
	function getAvailableServer($lobbyLogin, $scriptName, $titleIdString)
	{
		return $this->db->execute(
				'SELECT MS.login FROM MatchServers MS '.
				'WHERE MS.lobbyLogin = %s AND MS.scriptName = %s AND MS.titleIdString = %s '.
				'AND MS.`state` = %d AND matchId IS NULL '.
				'AND DATE_ADD(MS.lastLive, INTERVAL 20 SECOND) > NOW() '.
				'ORDER BY RAND() LIMIT 1',
				$this->db->quote($lobbyLogin), $this->db->quote($scriptName), $this->db->quote($titleIdString),
				\ManiaLivePlugins\MatchMakingLobby\Match\Plugin::SLEEPING
			)->fetchSingleValue(null);
	}

	/**
	 *
	 * @param string $playerLogin
	 * @param string $lobbyLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 */
	function isInMatch($playerLogin, $lobbyLogin, $scriptName, $titleIdString)
	{
		return ($this->getPlayerCurrentMatchId($playerLogin, $lobbyLogin, $scriptName, $titleIdString) === false ? false : true);
	}


	/**
	 *
	 * @param string $playerLogin
	 * @param string $lobbyLogin
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return boolean
	 */
	function getPlayerCurrentMatchId($playerLogin, $lobbyLogin, $scriptName, $titleIdString)
	{
		$matchIds = $this->getCurrentLobbyMatchIds($lobbyLogin, $scriptName, $titleIdString);
		if ($matchIds)
		{
			return $this->db->execute(
					'SELECT p.matchId '.
					'FROM Players p '.
					'WHERE p.matchId IN (%s) '.
					'AND p.login = %s '.
					'AND p.state NOT IN (%s)',
					implode(', ', $matchIds),
					$this->db->quote($playerLogin),
					implode(',', array(PlayerInfo::PLAYER_STATE_REPLACED, PlayerInfo::PLAYER_STATE_GIVE_UP, PlayerInfo::PLAYER_STATE_CANCEL))
				)->fetchSingleValue(false);
		}
		else
		{
			return false;
		}
	}

	function getPlayerCurrentMatch($playerLogin, $lobbyLogin, $scriptName, $titleIdString)
	{
		$matchId = $this->getPlayerCurrentMatchId($playerLogin, $lobbyLogin, $scriptName, $titleIdString);
		if ($matchId !== false)
		{
			return $this->getMatch($matchId);
		}
		else
		{
			return false;
		}
	}

	/**
	 * Updates match state
	 * @param int $matchId
	 * @param int $state
	 */
	function updateMatchState($matchId, $state)
	{
		$this->db->execute(
			'UPDATE Matches SET `state` = %d WHERE id=%d', $state, $matchId
		);
	}

	/**
	 * Register a match in database, the match Server will use this to ready up
	 * @param string $serverLogin
	 * @param \ManiaLivePlugins\MatchMakingLobby\Services\Match $match
	 * @param string $scriptName
	 * @param string $titleIdString
	 * @return int $matchId
	 */
	function registerMatch($serverLogin, Match $match, $scriptName, $titleIdString, $lobbyLogin)
	{
		$this->db->execute('BEGIN');
		try
		{
			$this->db->execute(
				'INSERT INTO Matches (creationDate, state, matchServerLogin, scriptName, titleIdString, lobbyLogin) '.
				'VALUES (NOW(), -1, %s, %s, %s, %s)',
				$this->db->quote($serverLogin),
				$this->db->quote($scriptName),
				$this->db->quote($titleIdString),
				$this->db->quote($lobbyLogin)
			);
			$matchId = $this->db->insertID();

			$this->updateServerCurrentMatchId($matchId, $serverLogin, $scriptName, $titleIdString);

			foreach($match->players as $player)
			{
				$this->addMatchPlayer($matchId, $player, $match->getTeam($player));
			}
			$this->db->execute('COMMIT');
		}
		catch(\Exception $e)
		{
			$this->db->execute('ROLLBACK');
			throw $e;
		}

		return $matchId;
	}

	/**
	 *
	 * @param int $matchId
	 * @param string $login
	 * @param int $teamId
	 */
	function addMatchPlayer($matchId, $login, $teamId)
	{
		switch($teamId)
		{
			case 1:
				$teamId = 0;
				break;
			case 2:
				$teamId = 1;
				break;
			case 0:
				$teamId = 'NULL';
				break;
			default :
				throw new \InvalidArgumentException;
		}
		$this->db->execute(
			'INSERT INTO Players (login, matchId, teamId, state) VALUES (%s,%d,%s, %d)',
			$this->db->quote($login),
			$matchId,
			$teamId,
			PlayerInfo::PLAYER_STATE_NOT_CONNECTED
		);
	}

	/**
	 * Set the new player state
	 * @param string $playerLogin
	 * @param int $matchId
	 * @param int $state
	 */
	function updatePlayerState($playerLogin, $matchId, $state)
	{
		$this->db->execute(
			'UPDATE Players SET state = %d WHERE login = %s AND matchId = %d',
			$state,
			$this->db->quote($playerLogin),
			$matchId
		);
	}

	/**
	 * Register the player rank on his match
	 * @param string $playerLogin
	 * @param int $matchId
	 * @param int $rank
	 */
	function updatePlayerRank($playerLogin, $matchId, $rank)
	{
		$this->db->execute(
			'UPDATE Players SET rank = %d WHERE login = %s AND matchId = %d',
			$rank,
			$this->db->quote($playerLogin),
			$matchId
		);
	}

	/**
	 * Register a server as match server
	 * @param string $serverLogin
	 * @param string $lobbyLogin
	 * @param string $state
	 */
	function registerMatchServer($serverLogin, $lobbyLogin, $state, $scriptName, $titleIdString)
	{
		$this->db->execute(
			'INSERT INTO MatchServers (login, lobbyLogin, state, lastLive, scriptName, titleIdString) '.
			'VALUES(%s, %s, %d, NOW(), %s, %s) '.
			'ON DUPLICATE KEY UPDATE state=VALUES(state), lobbyLogin=VALUES(lobbyLogin), lastLive=VALUES(lastLive)',
			$this->db->quote($serverLogin),
			$this->db->quote($lobbyLogin),
			$state,
			$this->db->quote($scriptName),
			$this->db->quote($titleIdString)
		);
	}

	function updateServerCurrentMatchId($matchId, $serverLogin, $scriptName, $titleIdString)
	{
		if(!$matchId) $matchId = 'NULL';
		else $matchId = (int) $matchId;

		$this->db->execute(
			'UPDATE MatchServers SET matchId = %s WHERE login = %s AND scriptName = %s AND titleIdString = %s ',
			$matchId,
			$this->db->quote($serverLogin),
			$this->db->quote($scriptName),
			$this->db->quote($titleIdString)
		);
	}

	/**
	 * Register a lobby server in the system
	 * @param string $lobbyLogin
	 * @param int $readyPlayersCount
	 * @param int $connectedPlayersCount
	 * @param string $serverName
	 * @param string $backLink
	 */
	function registerLobby($lobbyLogin, $readyPlayersCount, $connectedPlayersCount, $serverName, $backLink)
	{
		$this->db->execute(
			'INSERT INTO LobbyServers VALUES (%s, %s, %s, %d, %d) '.
			'ON DUPLICATE KEY UPDATE '.
			'name = VALUES(name), '.
			'backLink = VALUES(backLink), '.
			'readyPlayers = VALUES(readyPlayers), '.
			'connectedPlayers = VALUES(connectedPlayers) ',
			$this->db->quote($lobbyLogin), $this->db->quote($serverName), $this->db->quote($backLink),
			$readyPlayersCount, $connectedPlayersCount
		);
	}


	function createTables()
	{
		$this->db->execute(
			<<<EOLobbyServers
CREATE TABLE IF NOT EXISTS `LobbyServers` (
	`login` VARCHAR(25) NOT NULL,
	`name` VARCHAR(76) NOT NULL,
	`backLink` VARCHAR(76) NOT NULL,
	`readyPlayers` INT NOT NULL,
	`connectedPlayers` INT NOT NULL,
	PRIMARY KEY (`login`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOLobbyServers
		);

		$this->db->execute(
			<<<EOMatchServers
CREATE TABLE IF NOT EXISTS `MatchServers` (
	`login` VARCHAR(25) NOT NULL,
	`lobbyLogin` VARCHAR(25) NOT NULL,
	`state` INT NOT NULL COMMENT '-2: player left, -1 waiting, 1 sleeping, 2 deciding, 3 playing, 4 over',
	`lastLive` DATETIME NOT NULL,
	`scriptName` VARCHAR(75) NOT NULL,
	`titleIdString` VARCHAR(51) NOT NULL,
	`matchId` INT(10) NULL DEFAULT NULL,
	PRIMARY KEY (`login`, `scriptName`, `titleIdString`),
	INDEX `FK_MatchServers_Lobbies_idx` (`lobbyLogin`),
	CONSTRAINT `FK_MatchServers_LobbyServers` FOREIGN KEY (`lobbyLogin`) REFERENCES `LobbyServers` (`login`) ON UPDATE CASCADE ON DELETE NO ACTION
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOMatchServers
		);

		$this->db->execute(
			<<<EOMatches
CREATE TABLE IF NOT EXISTS `Matches` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`creationDate` DATETIME NOT NULL,
	`state` INT NOT NULL COMMENT '1:playing, -1: preparing, -2: player left, -3: player gave p, -4: over fine',
	`matchServerLogin` VARCHAR(25) NOT NULL,
	`scriptName` VARCHAR(75) NOT NULL,
	`titleIdString` VARCHAR(51) NOT NULL,
	`lobbyLogin` VARCHAR(25) NOT NULL,
	`lastUpdateDate` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX `FK_Matches_MatchServers_idx` (`matchServerLogin`),
	INDEX `FK_Matches_MatchServers` (`matchServerLogin`, `scriptName`, `titleIdString`),
	CONSTRAINT `FK_Matches_MatchServers` FOREIGN KEY (`matchServerLogin`, `scriptName`, `titleIdString`) REFERENCES `MatchServers` (`login`, `scriptName`, `titleIdString`) ON UPDATE CASCADE ON DELETE NO ACTION,
	CONSTRAINT `FK_Matches_LobbyServers` FOREIGN KEY (`lobbyLogin`) REFERENCES `LobbyServers` (`login`) ON UPDATE CASCADE ON DELETE NO ACTION
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOMatches
		);

		$this->db->execute(
			<<<EOPlayers
CREATE TABLE IF NOT EXISTS `Players` (
	`login` VARCHAR(25) NOT NULL,
	`matchId` INT NOT NULL,
	`teamId` INT NULL DEFAULT NULL,
	`state` INT NOT NULL DEFAULT '0',
	`rank` INT NOT NULL DEFAULT '0',
	PRIMARY KEY (`login`, `matchId`),
	INDEX `FK_Players_Matches_idx` (`matchId`),
	CONSTRAINT `FK_Players_Matches` FOREIGN KEY (`matchId`) REFERENCES `Matches` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOPlayers
		);
	}
}

?>
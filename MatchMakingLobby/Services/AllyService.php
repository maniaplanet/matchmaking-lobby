<?php

/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Services;

use ManiaLive\Database\MySQL\Connection;
use ManiaLive\DedicatedApi\Callback\Event;
use ManiaLive\Event\Dispatcher;

class AllyService implements \ManiaLive\DedicatedApi\Callback\Listener
{
	protected static $instances = array();

	/**
	 * @var Connection
	 */
	protected $db;

	protected $lobbyLogin;
	protected $scriptName;
	protected $titleIdString;

	static function getInstance($lobbyLogin = '', $scriptName = '', $titleIdString = '')
	{
		$class = get_called_class();
		if(!isset(self::$instances[$class]))
		{
			self::$instances[$class] = new $class($lobbyLogin, $scriptName, $titleIdString);
		}
		return self::$instances[$class];
	}

	protected function __construct($lobbyLogin, $scriptName, $titleIdString)
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

		Dispatcher::register(Event::getClass(), $this, Event::ON_PLAYER_ALLIES_CHANGED | Event::ON_PLAYER_CONNECT | Event::ON_PLAYER_DISCONNECT);
		$this->lobbyLogin = $lobbyLogin;
		$this->scriptName = $scriptName;
		$this->titleIdString = $titleIdString;
		$this->createTable();
	}

	public function onPlayerAlliesChanged($login)
	{
		$this->fireEvent($login);
	}

	public function onPlayerConnect($login, $isSpectator)
	{
		$allies = $this->get($login);
		foreach($allies as $ally)
		{
			if($this->isPlayerConnected($ally))
			{
				$this->fireEvent($ally);
			}
		}

		$logins = array_filter($this->getNonAnsweredLinked($login), array($this, 'isPlayerConnected'));
		foreach($logins as $login)
		{
			$this->fireEvent($login);
		}
	}

	public function onPlayerDisconnect($login, $disconnectionReason)
	{
		$allies = $this->get($login);
		foreach($allies as $ally)
		{
			if($this->isPlayerConnected($ally))
			{
				$this->fireEvent($ally);
			}
		}

		$logins = array_filter($this->getNonAnsweredLinked($login), array($this, 'isPlayerConnected'));
		foreach($logins as $login)
		{
			$this->fireEvent($login);
		}
	}

	public function set($playerLogin, $allyLogin)
	{
		$this->db->execute('INSERT IGNORE INTO Allies VALUES (%s, %s, %s, %s, %s, NULL)',
			$this->db->quote($playerLogin), $this->db->quote($allyLogin),
			$this->db->quote($this->lobbyLogin), $this->db->quote($this->scriptName), $this->db->quote($this->titleIdString)
		);
		$this->fireEvent($playerLogin);
		if($this->isAlly($playerLogin, $allyLogin))
		{
			$this->fireEvent($allyLogin);
		}


	}

	public function setPlayerAway($login)
	{
		$this->db->execute(
			'UPDATE Allies SET disconnectionDate = NOW() '.
			'WHERE playerLogin = %s AND lobbyLogin = %s AND scriptName = %s and titleIdString = %s', $this->db->quote($login),
			$this->db->quote($this->lobbyLogin), $this->db->quote($this->scriptName), $this->db->quote($this->titleIdString)
		);
	}

	public function setPlayerPresent($login)
	{
		$this->db->execute(
			'UPDATE Allies SET disconnectionDate = NULL '.
			'WHERE playerLogin = %s AND lobbyLogin = %s AND scriptName = %s and titleIdString = %s', $this->db->quote($login),
			$this->db->quote($this->lobbyLogin), $this->db->quote($this->scriptName), $this->db->quote($this->titleIdString)
		);
	}

	public function removePlayerAway()
	{
		$this->db->execute('DELETE FROM Allies WHERE DATE_ADD(disconnectionDate, INTERVAL 30 MINUTE) < NOW() '.
			'AND lobbyLogin = %s AND scriptName = %s AND titleIdString = %s', $this->db->quote($this->lobbyLogin),
			$this->db->quote($this->scriptName), $this->db->quote($this->titleIdString)
		);
	}


	public function remove($playerLogin, $allyLogin)
	{
		$fireEvent = false;
		if($this->isAlly($playerLogin, $allyLogin))
		{
			$fireEvent = true;
		}

		$this->db->execute(
			'DELETE FROM Allies WHERE playerLogin = %s AND allyLogin = %s '.
			'AND lobbyLogin = %s AND scriptName = %s AND titleIdString = %s',
			$this->db->quote($playerLogin), $this->db->quote($allyLogin),
			$this->db->quote($this->lobbyLogin), $this->db->quote($this->scriptName), $this->db->quote($this->titleIdString)
			);

		$this->fireEvent($playerLogin);
		if($fireEvent)
		{
			$this->fireEvent($allyLogin);
		}
	}

	public function get($playerLogin)
	{
		$allies = $this->getDedicatedAllies($playerLogin);
		$localAllies = $this->db->execute(
			'SELECT A1.allyLogin as login '.
			'FROM Allies A1 '.
			'INNER JOIN Allies A2 ON A1.playerLogin = A2.allyLogin AND A1.allyLogin = A2.playerLogin '.
			'AND A1.lobbyLogin = A2.lobbyLogin AND A1.scriptName = A2.scriptName AND A1.titleIdString = A2.titleIdString '.
			'WHERE A1.playerLogin = %s '.
			'AND A1.lobbyLogin = %s AND A1.scriptName = %s AND A1.titleIdString = %s',
			$this->db->quote($playerLogin),
			$this->db->quote($this->lobbyLogin), $this->db->quote($this->scriptName), $this->db->quote($this->titleIdString)
		)->fetchArrayOfSingleValues();
		$localAllies = array_filter($localAllies, array($this, 'isPlayerConnected'));
		return array_unique(array_merge($allies, $localAllies));
	}

	public function getAll($playerLogin)
	{
		$generalAllies = $this->getDedicatedAllies($playerLogin);
		$allyList = array();
		foreach($generalAllies as $ally)
		{
			$obj = new Ally();
			$obj->login = $ally;
			$obj->type = Ally::TYPE_GENERAL;
			$obj->isBilateral = true;
			$allyList[] = $obj;
		}
		$localAllies = $this->db->execute(
				'SELECT A1.allyLogin as login, IF(A2.playerLogin IS NULL, FALSE, TRUE) as isBilateral, %d as type '.
				'FROM Allies A1 '.
				'LEFT JOIN Allies A2 ON A1.playerLogin = A2.allyLogin AND A1.allyLogin = A2.playerLogin '.
				'AND A1.lobbyLogin = A2.lobbyLogin AND A1.scriptName = A2.scriptName AND A1.titleIdString = A2.titleIdString '.
				'WHERE A1.playerLogin = %s '.
				'AND A1.lobbyLogin = %s AND A1.scriptName = %s AND A1.titleIdString = %s',
				Ally::TYPE_LOCAL,
				$this->db->quote($playerLogin),
				$this->db->quote($this->lobbyLogin), $this->db->quote($this->scriptName), $this->db->quote($this->titleIdString)
			)->fetchArrayOfObject('\ManiaLivePlugins\MatchMakingLobby\Services\Ally');
		foreach($localAllies as $ally)
		{
			if($this->isPlayerConnected($ally->login))
			{
				$allyList[] = $ally;
			}
		}
		return $allyList;
	}

	protected function getDedicatedAllies($playerLogin)
	{
		$player = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($playerLogin);
		return $player ? $player->allies : array();
	}


	protected function getNonAnsweredLinked($allyLogin)
	{
		$logins = $this->db->execute(
			'SELECT A1.playerLogin '.
			'FROM Allies A1 '.
			'LEFT JOIN Allies A2 ON A1.allyLogin = A2.playerLogin '.
			'AND A1.lobbyLogin = A2.lobbyLogin AND A1.scriptName = A2.scriptName AND A1.titleIdString = A2.titleIdString '.
			'WHERE A1.allyLogin = %s AND (A1.playerLogin != A2.allyLogin OR A2.allyLogin IS NULL) '.
			'AND A1.lobbyLogin = %s AND A1.scriptName = %s AND A1.titleIdString = %s',
			$this->db->quote($allyLogin),
			$this->db->quote($this->lobbyLogin), $this->db->quote($this->scriptName), $this->db->quote($this->titleIdString)
		)->fetchArrayOfSingleValues();
		return array_filter($logins, array($this,'isPlayerConnected'));
	}

	public function isAlly($playerLogin, $allyLogin)
	{
		return in_array($allyLogin, $this->get($playerLogin));
	}

	protected function fireEvent($playerLogin)
	{
		Dispatcher::dispatch(new AllyEvent($playerLogin));
	}

	protected function isPlayerConnected($login)
	{
		$p = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($login);
		return ($p && $p->isConnected !== false ? true : false);
	}

	function createTable()
	{
		$this->db->execute(
			<<<EOAlly
CREATE TABLE IF NOT EXISTS `Allies` (
	`playerLogin` VARCHAR(25) NOT NULL,
	`allyLogin` VARCHAR(25) NOT NULL,
	`lobbyLogin` VARCHAR(25) NOT NULL,
	`scriptName` VARCHAR(75) NOT NULL,
	`titleIdString` VARCHAR(51) NOT NULL,
	`disconnectionDate` DATETIME NULL DEFAULT NULL,
	PRIMARY KEY (`playerLogin`, `allyLogin`, `lobbyLogin`, `scriptName`, `titleIdString`),
	INDEX `allyLogin` (`allyLogin`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
EOAlly
		);
	}



	public function onBeginMap($map, $warmUp, $matchContinuation)
	{

	}

	public function onBeginMatch()
	{

	}

	public function onBeginRound()
	{

	}

	public function onBillUpdated($billId, $state, $stateName, $transactionId)
	{

	}

	public function onEcho($internal, $public)
	{

	}

	public function onEndMap($rankings, $map, $wasWarmUp, $matchContinuesOnNextMap, $restartMap)
	{

	}

	public function onEndMatch($rankings, $winnerTeamOrMap)
	{

	}

	public function onEndRound()
	{

	}

	public function onManualFlowControlTransition($transition)
	{

	}

	public function onMapListModified($curMapIndex, $nextMapIndex, $isListModified)
	{

	}

	public function onModeScriptCallback($param1, $param2)
	{

	}

	public function onPlayerChat($playerUid, $login, $text, $isRegistredCmd)
	{

	}

	public function onPlayerCheckpoint($playerUid, $login, $timeOrScore, $curLap, $checkpointIndex)
	{

	}

	public function onPlayerFinish($playerUid, $login, $timeOrScore)
	{

	}

	public function onPlayerIncoherence($playerUid, $login)
	{

	}

	public function onPlayerInfoChanged($playerInfo)
	{

	}

	public function onPlayerManialinkPageAnswer($playerUid, $login, $answer, array $entries)
	{

	}

	public function onServerStart()
	{

	}

	public function onServerStop()
	{

	}

	public function onStatusChanged($statusCode, $statusName)
	{

	}

	public function onTunnelDataReceived($playerUid, $login, $data)
	{

	}

	public function onVoteUpdated($stateName, $login, $cmdName, $cmdParam)
	{

	}
}

?>

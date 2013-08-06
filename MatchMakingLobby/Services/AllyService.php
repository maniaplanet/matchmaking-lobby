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

class AllyService extends \ManiaLib\Utils\Singleton implements \ManiaLive\DedicatedApi\Callback\Listener
{

	/**
	 * @var Connection
	 */
	protected $db;
	
	protected function __construct()
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
		$this->createTable();
	}
	
	public function onPlayerAlliesChanged($login)
	{
		$this->fireEvent($login);
	}
	
	public function onPlayerConnect($login, $isSpectator)
	{
		$logins = array_merge($this->get($login), $this->getNonAnsweredLinked($login));
		foreach($logins as $login)
		{
			$this->fireEvent($login);
		}
	}

	public function onPlayerDisconnect($login, $disconnectionReason)
	{
		$logins = array_merge($this->get($login), $this->getNonAnsweredLinked($login));
		foreach($logins as $login)
		{
			$this->fireEvent($login);
		}
	}
	
	public function set($playerLogin, $allyLogin)
	{
		$this->db->execute('INSERT IGNORE INTO Allies VALUES (%1$s, %2$s)', $this->db->quote($playerLogin), $this->db->quote($allyLogin));
		$this->fireEvent($playerLogin);
	}
	
	public function remove($playerLogin, $allyLogin)
	{
		$this->db->execute('DELETE FROM Allies WHERE playerLogin = %s AND allyLogin = %s', $this->db->quote($playerLogin), $this->db->quote($allyLogin));
		$this->fireEvent($playerLogin);
	}
	
	public function get($playerLogin)
	{
		$allies = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($playerLogin)->allies;
		$localAllies = $this->db->execute(
			'SELECT A1.allyLogin '.
			'FROM Allies A1 '.
			'INNER JOIN Allies A2 ON A1.playerLogin = A2.allyLogin '.
			'WHERE A1.playerLogin = %s AND A1.allyLogin = A2.playerLogin',
			$this->db->quote($playerLogin)
		)->fetchArrayOfSingleValues();
		$result = array_merge($allies, $localAllies);
		return array_filter(array_unique($result), array($this,'isPlayerConnected'));
	}
	
	public function getNonBilateralAlliances($playerLogin)
	{
		$logins = $this->db->execute(
			'SELECT A1.allyLogin '.
			'FROM Allies A1 '.
			'LEFT JOIN Allies A2 ON A1.playerLogin = A2.allyLogin '.
			'WHERE A1.playerLogin = %s AND (A1.allyLogin != A2.playerLogin OR A2.playerLogin IS NULL)',
			$this->db->quote($playerLogin)
		)->fetchArrayOfSingleValues();
		
		return array_filter($logins, array($this,'isPlayerConnected'));
	}
	
	protected function getNonAnsweredLinked($allyLogin)
	{
		$logins = $this->db->execute(
			'SELECT A1.playerLogin '.
			'FROM Allies A1 '.
			'LEFT JOIN Allies A2 ON A1.allyLogin = A2.playerLogin '.
			'WHERE A1.allyLogin = %s AND (A1.playerLogin != A2.allyLogin OR A2.allyLogin IS NULL)',
			$this->db->quote($allyLogin)
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
		return ($p ? $p->isConnected : false);
	}
		
	function createTable()
	{
		$this->db->execute(
			<<<EOAlly
CREATE TABLE IF NOT EXISTS `Allies` (
	`playerLogin` VARCHAR(25) NOT NULL,
	`allyLogin` VARCHAR(25) NOT NULL,
	PRIMARY KEY (`playerLogin`, `allyLogin`),
	INDEX `allyLogin` (`allyLogin`)
)
COLLATE='utf8_general_ci'
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

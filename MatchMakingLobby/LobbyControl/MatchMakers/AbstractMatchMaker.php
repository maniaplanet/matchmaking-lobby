<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\LobbyControl\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\LobbyControl\Match;
use ManiaLivePlugins\MatchMakingLobby\LobbyControl\Helpers;
use ManiaLivePlugins\MatchMakingLobby\LobbyControl\PlayerInfo;

abstract class AbstractMatchMaker extends \ManiaLib\Utils\Singleton
{

	const WAITING_STEP = 60;
	const DISTANCE_THRESHOLD = 300;

	/**
	 * if override to true. Override method distributePlayers to put players in different teams
	 * @var bool
	 */
	protected $isTeamMode = false;

	/** @var Helpers\Graph */
	protected $graph;
	public $playerPerMatch = 0;

	final function run(array $bannedPlayers = array())
	{
		$matches = array();
		$this->buildGraph($bannedPlayers);

		$nodes = $this->graph->getNodes();
		while($nodes && $cliques = $this->graph->findCliques(reset($nodes), $this->playerPerMatch, static::DISTANCE_THRESHOLD))
		{
			usort($cliques,
				function($a, $b)
				{
					$radiusDiff = $a->getRadius() - $b->getRadius();
					return $radiusDiff < 0 ? -1 : ($radiusDiff > 0 ? 1 : 0);
				});
			$match = new Match();
			$match->players = reset($cliques)->getNodes();
			if($this->isTeamMode)
			{
				$match = $this->distributePlayers($match);
			}
			$matches[] = $match;
			$this->graph->deleteNodes(reset($cliques)->getNodes());
			$nodes = $this->graph->getNodes();
		}
		return $matches;
	}

	final protected function buildGraph(array $bannedPlayers = array())
	{
		$this->graph = new Helpers\Graph();

		$readyPlayers = PlayerInfo::GetReady();
		$followers = array_filter($readyPlayers, function ($f) { return !$f->isInMatch(); });
		$followers = array_filter($followers, function ($f) use ($bannedPlayers) { return !in_array($f->login, $bannedPlayers); });
		
		while($player = array_shift($followers))
		{
			$this->graph->addNode(
				$player->login, 
				$this->computeDistances($player, $followers)
				);
		}
	}

	/**
	 * @param PlayerInfo $player
	 * @param PlayerInfo[] $followers
	 * @return float[string]
	 */
	final protected function computeDistances($player, $followers)
	{
		$distances = array();
		foreach($followers as $follower)
		{
			if($follower->login == $player->login)
				continue;
			$distances[$follower->login] = $this->distance($player, $follower);
		}
		return $distances;
	}

	/**
	 * @param PlayerInfo $p1
	 * @param PlayerInfo $p2
	 */
	protected function distance($p1, $p2)
	{
		$distance = abs($p1->ladderPoints - $p2->ladderPoints);

		// Waiting time coefficient
		$waitingTime = $p1->getWaitingTime() + $p2->getWaitingTime();
		$distance *= exp(-log(2) * $waitingTime / self::WAITING_STEP);

		return $distance;
	}

	protected function distributePlayers(Match $m)
	{
		
	}
	
	abstract function getPlayerScore($login);
}

?>
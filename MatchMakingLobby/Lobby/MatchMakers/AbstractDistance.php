<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\Lobby\Helpers\Team;
use ManiaLivePlugins\MatchMakingLobby\Services\Match;
use ManiaLivePlugins\MatchMakingLobby\Lobby\Helpers;
use ManiaLivePlugins\MatchMakingLobby\Lobby\Helpers\DistanciableObject;

/**
 * Extend this and implement the two abstracts methods to have a distance based match maker
 *
 */
abstract class AbstractDistance extends \ManiaLib\Utils\Singleton implements MatchMakerInterface
{

	/**
	 * Lower waiting step = faster decrease
	 */
	const WAITING_STEP = 40;

	/**
	 * Threshold used to make team with near players
	 */
	const DISTANCE_PLAYERS_THRESHOLD = 20000;

	/**
	 * Threshold used to matchmake teams
	 */
	const DISTANCE_TEAMS_THRESHOLD = 30000;

	/** @var Helpers\Graph */
	protected $playerGraph;

	/** @var Helpers\Graph */
	protected $teamsGraph;

	/**
	 * Return the distance between two players
	 * @param string $p1
	 * @param string $p2
	 * @return int
	 */
	abstract protected function playersDistance($p1, $p2);

	/**
	 * Return the distance between two teams
	 * @param string $p1
	 * @param string $p2
	 * @return int
	 */
	abstract protected function teamsDistance($t1, $t2);

	/**
	 * @param string[]
	 * @return array
	 */
	abstract protected function dispatch($players);

	/**
	 * Entry point for the match making
	 * @param array $players
	 * @return Match
	 */
	function run(array $players = array())
	{
		$teams = $this->getTeams($players);

		return $this->getMatches($teams);
	}

	function getTeams(array $players = array())
	{
		$this->buildPlayersGraph($players);

		$nodes = $this->playerGraph->getNodes();

		$numberOfPlayer = ($this->getNumberOfTeam() == 0) ? $this->getPlayersPerMatch() : $this->getPlayersPerMatch()/$this->getNumberOfTeam();

		$teams = array();

		while($nodes && $cliques = $this->playerGraph->findCliques(reset($nodes), $numberOfPlayer, static::DISTANCE_PLAYERS_THRESHOLD))
		{
			$teams[] = reset($cliques)->getNodes();

			$this->playerGraph->deleteNodes(reset($cliques)->getNodes());
			$nodes = $this->playerGraph->getNodes();
		}
		$teams = array_map(function ($team) { return new Team($team); }, $teams);

		unset($this->playerGraph); //Memory Usage

		return $teams;
	}

	public function getMatches(array $teams = array())
	{
		$matches = array();

		if ($this->getNumberOfTeam() == 0)
		{
			foreach ($teams as $team)
			{
				$match = new Match();
				$match->players = $team->data;

				$matches[] = $match;
			}
		}
		else
		{
			$this->buildTeamsGraph($teams);

			$nodes = $this->teamsGraph->getNodes();

			while($nodes && $cliques = $this->teamsGraph->findCliques(reset($nodes), $this->getNumberOfTeam(), static::DISTANCE_TEAMS_THRESHOLD))
			{
				usort($cliques,
					function($a, $b)
					{
						$radiusDiff = $a->getRadius() - $b->getRadius();
						return $radiusDiff < 0 ? -1 : ($radiusDiff > 0 ? 1 : 0);
					});

				$temp = reset($cliques)->getNodes();

				$t = array();
				foreach($teams as $team)
				{
					$t[$team->id] = $team;
				}

				$match = new Match();
				$match->players = array_merge($this->teamsGraph->data[$temp[0]], $this->teamsGraph->data[$temp[1]]);

				//If teams are not immuable, we can modify them !
				if (!$t[$temp[0]]->immuable && !$t[$temp[0]]->immuable)
				{
					list($team1,$team2) = $this->dispatch($match->players);
				}
				else
				{
					$team1 = $this->teamsGraph->data[$temp[0]];
					$team2 = $this->teamsGraph->data[$temp[1]];
				}
				$match->team1 = $team1;
				$match->team2 = $team2;

				$matches[] = $match;

				$this->teamsGraph->deleteNodes(reset($cliques)->getNodes());
				$nodes = $this->teamsGraph->getNodes();

			}
			unset($this->teamsGraph); //Memory Usage
		}
		return $matches;
	}

	protected function buildPlayersGraph(array $players = array())
	{
		$distanciablePlayers = array_map(function ($player) { return new DistanciableObject($player, $player); }, $players);

		$this->buildGraph($this->playerGraph, array($this, 'playersDistance'), $distanciablePlayers);

		return $this->playerGraph;
	}

	protected function buildTeamsGraph(array $teams = array())
	{
		$this->buildGraph($this->teamsGraph, array($this, 'teamsDistance'), $teams);

		return $this->teamsGraph;
	}

	/**
	 * Create a graph where each ready player is a node
	 * @param DistanciableObject[] $bannedPlayers
	 * @return type
	 */
	protected function buildGraph(&$graph, $distanceComputeCallback, array $objects)
	{
		$graph = new Helpers\Graph();

		while($object = array_shift($objects))
		{
			$graph->addNode(
				$object,
				$this->computeDistances($object, $objects, $distanceComputeCallback)
				);
		}
	}

	/**
	 * Compute distance for a player with all his followers
	 * @param string $player
	 * @param string[] $followers
	 * @return float[string]
	 */
	private function computeDistances($object, $followers, $distanceComputeCallback)
	{
		$distances = array();
		foreach($followers as $follower)
		{
			if($follower == $object)
				continue;
			$distances[$follower->id] = call_user_func($distanceComputeCallback, $object->data, $follower->data);
		}
		return $distances;
	}
}

?>
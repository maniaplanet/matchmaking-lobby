<?php
/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Lobby\MatchMakers;

use ManiaLivePlugins\MatchMakingLobby\Services\Match;
use ManiaLivePlugins\MatchMakingLobby\Lobby\Helpers;
use ManiaLivePlugins\MatchMakingLobby\Lobby\Helpers\DistanciableObject;

abstract class AbstractDistance extends \ManiaLib\Utils\Singleton implements MatchMakerInterface
{

	const WAITING_STEP = 60;

	const DISTANCE_PLAYERS_THRESHOLD = 300;

	const DISTANCE_TEAMS_THRESHOLD = 30000;

	/** @var Helpers\Graph */
	protected $playerGraph;

	/** @var Helpers\Graph */
	protected $teamsGraph;

	/**
	 * Entry point for the match making
	 * @param array $players
	 * @return Match
	 */
	function run(array $players = array())
	{
		$teams = $this->getTeams();

		return $this->getMatches($teams);
	}

	function getTeams(array $players = array())
	{
		$this->buildPlayersGraph($players);

		$nodes = $this->playerGraph->getNodes();

		$numberOfPlayer = ($this->getNumberOfTeam() == 0) ? $this->getPlayersPerMatch() : $this->getPlayersPerMatch()/$this->getNumberOfTeam();

		$teams = array();

		while($nodes && $cliques = $this->playerGraph->findCliques(reset($nodes), $numberOfPlayer, static::DISTANCE_TEAMS_THRESHOLD))
		{
			usort($cliques,
				function($a, $b)
				{
					$radiusDiff = $a->getRadius() - $b->getRadius();
					return $radiusDiff < 0 ? -1 : ($radiusDiff > 0 ? 1 : 0);
				});

			$teams[] = reset($cliques)->getNodes();

			$this->playerGraph->deleteNodes(reset($cliques)->getNodes());
			$nodes = $this->playerGraph->getNodes();
		}
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
				$match->players = $team;

				$matches[] = $match;
			}
		}
		else
		{
			$this->buildTeamsGraph($teams);

			$nodes = $this->teamsGraph->getNodes();

			$teams = array();

			while($nodes && $cliques = $this->teamsGraph->findCliques(reset($nodes), $this->getNumberOfTeam(), static::DISTANCE_TEAMS_THRESHOLD))
			{
				usort($cliques,
					function($a, $b)
					{
						$radiusDiff = $a->getRadius() - $b->getRadius();
						return $radiusDiff < 0 ? -1 : ($radiusDiff > 0 ? 1 : 0);
					});

				$temp = reset($cliques)->getNodes();

				$match = new Match();

				$match->team1 = $this->teamsGraph->data[$temp[0]];
				$match->team2 = $this->teamsGraph->data[$temp[1]];

				$matches[] = $match;

				$this->teamsGraph->deleteNodes(reset($cliques)->getNodes());
				$nodes = $this->teamsGraph->getNodes();
			}
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
		$distanciableTeam = array_map(function ($team) { return new DistanciableObject(serialize($team), $team); }, $teams);

		$this->buildGraph($this->teamsGraph, array($this, 'teamsDistance'), $distanciableTeam);

		return $this->teamsGraph;
	}

	/**
	 * Create a graph where each ready player is a node
	 * @param DistanciableObject[] $bannedPlayers
	 * @return type
	 */
	protected function buildGraph(&$graph, $distanceComputeCallback, array $objects)
	{
		//TODO: check if $objects if array of DistanciableObject

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
}

?>
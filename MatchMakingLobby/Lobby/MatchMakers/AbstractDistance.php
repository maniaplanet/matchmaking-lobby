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
use ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo;

abstract class AbstractDistance extends \ManiaLib\Utils\Singleton implements MatchMakerInterface
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

	/**
	 * Entry point for the match making
	 * @param array $players
	 * @return Match
	 */
	function run(array $players = array())
	{
		$matches = array();
		$this->buildGraph($players);

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

	/**
	 * Create a graph where each ready player is a node
	 * @param array $bannedPlayers
	 * @return type
	 */
	protected function buildGraph(array $players = array())
	{
		$this->graph = new Helpers\Graph();
		$matchMakingService = new \ManiaLivePlugins\MatchMakingLobby\Services\MatchMakingService();

		while($player = array_shift($players))
		{
			$this->graph->addNode(
				$player->login,
				$this->computeDistances($player, $players)
				);
		}
	}

	/**
	 * Compute distance for a player with all his followers
	 * @param PlayerInfo $player
	 * @param PlayerInfo[] $followers
	 * @return float[string]
	 */
	private function computeDistances($player, $followers)
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
	 * Return the distance between two players
	 * @param PlayerInfo $p1
	 * @param PlayerInfo $p2
	 * @return int
	 */
	abstract protected function distance(PlayerInfo $p1, PlayerInfo $p2);

	/**
	 * Get players from Match->players and add them in teams
	 * @param Match $match input match
	 * @return Match output match
	 */
	abstract protected function distributePlayers(Match $match);

}

?>
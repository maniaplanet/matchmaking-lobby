<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\ManiaHall\LobbyControl;

class MatchMaker extends \ManiaLib\Utils\Singleton
{
	const WAITING_STEP = 60;
	const DISTANCE_THRESHOLD = 500;
	
	/** @var Helpers\Graph */
	private $graph;
	
	function run($matchSize)
	{
		$matches = array();
		$this->buildGraph();
		
		$nodes = $this->graph->getNodes();
		while($nodes && $cliques = $this->graph->findCliques(reset($nodes), $matchSize, self::DISTANCE_THRESHOLD))
		{
			usort($cliques, function($a, $b) {
				$radiusDiff = $a->getRadius() - $b->getRadius();
				return $radiusDiff < 0 ? -1 : ($radiusDiff > 0 ? 1 : 0);
			});
			$matches[] = reset($cliques)->getNodes();
			$this->graph->deleteNodes(reset($cliques)->getNodes());
			$nodes = $this->graph->getNodes();
		}
		
		return $matches;
	}
	
	private function buildGraph()
	{
		$this->graph = new Helpers\Graph();
		
		$followers = PlayerInfo::GetReady();
		while($player = array_shift($followers))
			$this->graph->addNode($player->login, $this->computeDistances($player, $followers));
	}
	
	/**
	 * @param PlayerInfo $player
	 * @param PlayerInfo[] $followers
	 * @return float[string]
	 */
	private function computeDistances($player, $followers)
	{
		$distances = array();
		foreach($followers as $follower)
			$distances[$follower->login] = $this->distance($player, $follower);
		return $distances;
	}
	
	/**
	 * @param PlayerInfo $p1
	 * @param PlayerInfo $p2
	 */
	private function distance($p1, $p2)
	{
		$distance = abs($p1->ladderPoints - $p2->ladderPoints);
		
		// Waiting time coefficient
		$waitingTime = $p1->getWaitingTime() + $p2->getWaitingTime();
		$distance *= exp(-log(2) * $waitingTime / self::WAITING_STEP);
		
		return $distance;
	}
}

?>

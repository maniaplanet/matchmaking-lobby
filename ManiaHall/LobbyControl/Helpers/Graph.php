<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\ManiaHall\LobbyControl\Helpers;

class Graph
{
	/** @var float[string][string] */
	private $distances = array();
	
	/**
	 * @param string $name
	 * @param float[string] $distances
	 */
	function addNode($name, $distances)
	{
		$this->distances[$name] = $distances;
	}
	
	/**
	 * @param string[] $names
	 */
	function deleteNodes($names)
	{
		$diffArray = array_fill_keys($names, 0);
		$this->distances = array_diff_key($this->distances, $diffArray);
		foreach($this->distances as &$followersDistances)
			$followersDistances = array_diff_key($followersDistances, $diffArray);
	}
	
	/**
	 * @return string[]
	 */
	function getNodes()
	{
		return array_keys($this->distances);
	}
	
	/**
	 * @param string $name
	 * @param float $threshold
	 * @return float[string]
	 */
	function getNeighbours($name, $threshold)
	{
		return array_filter($this->distances[$name], function($d) use($threshold) { return $d <= $threshold; });
	}
	
	/**
	 * @param int $size
	 * @param float $threshold
	 * @return Clique[]
	 */
	function findCliques($startNode, $size, $threshold)
	{
		$cliques = array();
		$temp[] = new Clique($startNode, $this->getNeighbours($startNode, $threshold));
		
		while($clique = array_shift($temp))
		{
			foreach(array_keys($clique->getNeighbours()) as $neighbourName)
			{
				$extendedClique = clone $clique;
				$extendedClique->addNode($neighbourName, $this->getNeighbours($neighbourName, $threshold));
				if($extendedClique->getSize() == $size)
					$cliques[] = $extendedClique;
				else if($extendedClique->getPossibleSize() >= $size)
					$temp[] = $extendedClique;
			}
		}
		
		return $cliques;
	}
}

?>

<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\ManiaHall\LobbyControl\Helpers;

class Clique
{
	/** @var string[] */
	private $nodes;
	/** @var float[string] */
	private $neighbours;
	/** @var float */
	private $radius;
	
	/**
	 * @param string $name
	 * @param float[string] $neighbours
	 */
	function __construct($name, $neighbours)
	{
		$this->nodes[] = $name;
		$this->neighbours = $neighbours;
		$this->radius = 0;
	}
	
	/**
	 * @param string $name
	 * @param float[string] $neighbours
	 */
	function addNode($name, $neighbours)
	{
		$this->nodes[] = $name;
		$this->radius = max($this->radius, $this->neighbours[$name]);
		$this->neighbours = array_intersect_key($this->neighbours, $neighbours);
		foreach($this->neighbours as $name => $distance)
			$this->neighbours[$name] = max($distance, $neighbours[$name]);
	}
	
	/**
	 * @return string[]
	 */
	function getNodes()
	{
		return $this->nodes;
	}
	
	/**
	 * @return int
	 */
	function getSize()
	{
		return count($this->nodes);
	}
	
	/**
	 * @return int
	 */
	function getPossibleSize()
	{
		return $this->getSize()+count($this->neighbours);
	}
	
	/**
	 * @return float
	 */
	function getRadius()
	{
		return $this->radius;
	}
	
	/**
	 * @return float[string]
	 */
	function getNeighbours()
	{
		return $this->neighbours;
	}
	
	/**
	 * @return string
	 */
	function __toString()
	{
		return implode(',', $this->nodes);
	}
}

?>

<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Lobby\Helpers;

class Team extends DistanciableObject
{
	public $immuable;

	public function __construct($players = array(), $immuable = false)
	{
		$this->id = serialize($players);
		$this->data = $players;
		$this->immuable = $immuable;
	}
}
?>
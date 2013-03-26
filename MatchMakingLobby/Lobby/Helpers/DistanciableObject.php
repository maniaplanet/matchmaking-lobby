<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Lobby\Helpers;

class DistanciableObject
{
	public $id;

	public $data;

	public function __construct($id, $data)
	{
		$this->id = $id;
		$this->data = $data;
	}
}
?>
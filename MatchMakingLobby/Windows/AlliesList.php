<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLivePlugins\MatchMakingLobby\Controls\PlayerDetailed;

class AlliesList extends \ManiaLive\Gui\Window
{
	protected $playerList = array();
	
	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $frame;
	
	protected function onConstruct()
	{
		$this->setSize(70, 100);

		$this->frame = new \ManiaLive\Gui\Controls\Frame(0,0, new \ManiaLib\Gui\Layouts\Column());
		$this->addComponent($this->frame);
	}
	
	static function addPlayer($login, $state = 0, $zone = 'World', $ladderPoints = -1)
	{
		$storage = \ManiaLive\Data\Storage::getInstance();
		try
		{
			$playerObj = $storage->getPlayerObject($login);
			$this->playerList[$login] = new PlayerDetailed($playerObj ? $playerObj->nickName : $login);
			$this->playerList[$login]->setState($state, $zone, $ladderPoints);
		}
		catch(\Exception $e)
		{
			return;
		}
	}
	
	static function removePlayer($login)
	{
		if(array_key_exists($login, self::$playerList))
		{
			unset($this->playerList[$login]);
		}
	}
}

?>

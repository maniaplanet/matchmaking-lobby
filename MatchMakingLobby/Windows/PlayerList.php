<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9091 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-12 16:37:36 +0100 (mer., 12 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLivePlugins\MatchMakingLobby\Controls\Player;

class PlayerList extends \ManiaLive\Gui\Window
{
	static protected $playerList = array();
	
	public $smallCards = false;

	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $frame;

	protected function onConstruct()
	{
		$this->setSize(70, 100);

		$this->frame = new \ManiaLive\Gui\Controls\Frame(0,0, new \ManiaLib\Gui\Layouts\Column());
		$this->frame->getLayout()->setMarginHeight(0.5);
		$this->addComponent($this->frame);
	}

	static function addPlayer($login, $state = 0, $zone = 'World', $ladderPoints = -1, $flagURL = '')
	{
		$storage = \ManiaLive\Data\Storage::getInstance();
		try
		{
			$playerObj = $storage->getPlayerObject($login);
			self::$playerList[$login] = array(
				'nickname' => $playerObj ? $playerObj->nickName : $login,
				'zone' => $zone,
				'ladderPoints' => $ladderPoints,
				'state' => $state,
				'zoneFlag' => $flagURL
				);
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
			unset(self::$playerList[$login]);
		}
	}
	
	static function setPlayer($login, $state, $zone = 'World', $ladderPoints = -1, $flagUrl = '')
	{
		if(array_key_exists($login, self::$playerList))
		{
			self::$playerList[$login]['state'] = $state;
			self::$playerList[$login]['zone'] = $zone;
			self::$playerList[$login]['ladderPoints'] = $ladderPoints;
		}
		else
		{
			self::addPlayer($login, $state, $zone, $ladderPoints, $flagUrl);
		}
	}
	
	function onDraw()
	{
		$this->updateItemList();
		$this->posZ = 5;
	}

	protected function updateItemList()
	{
		$this->frame->clearComponents();
		
		uasort(self::$playerList,
			function ($p1, $p2)
			{
				if($p1['state'] == $p2['state'])
				{
					if($p1['ladderPoints'] == $p2['ladderPoints'])
					{
						return 0;
					}
					return $p1['ladderPoints'] > $p2['ladderPoints'] ? 1 : -1;
				}
				return $p1['state'] > $p2['state'] ? -1 : 1;
			}
		);
		
		foreach(self::$playerList as $player)
		{
			$component = new Player($player['nickname']);
			$component->state = $player['state'];
			$component->ladderPoints = $player['ladderPoints'];
			$component->zoneFlagURL = $player['zoneFlag'];
			if($this->smallCards)
			{
				$component->setSizeX(45);
			}
			$this->frame->addComponent($component);
		}
	}


}

?>
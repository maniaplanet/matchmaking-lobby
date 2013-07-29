<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9091 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-12 16:37:36 +0100 (mer., 12 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;
use ManiaLivePlugins\MatchMakingLobby\Controls\Player;
use ManiaLivePlugins\MatchMakingLobby\Controls\PlayerSmall;

class PlayerList extends \ManiaLive\Gui\Window
{
	const SIZE_X = 52;
	const SIZE_Y = 127;
	
	static protected $playerList = array();
	
	public $smallCards = false;
	
	public $bg;
	
	public $title;

	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $frame;
	
	/**
	 * @var \ManiaLive\Gui\Controls\Pager 
	 */
	protected $pager;

	protected function onConstruct()
	{
		$this->setLayer(\ManiaLive\Gui\Window::LAYER_CUT_SCENE);
		
		$this->setSize(self::SIZE_X, self::SIZE_Y);
		
		$this->setAlign('right', 'center');
		
		//162
		$this->setPosition(163, 0, 15);
		
		$this->bg = new \ManiaLib\Gui\Elements\Quad(self::SIZE_X, self::SIZE_Y);
		$this->bg->setImage('file://Media/Manialinks/Common/Lobbies/side-frame.png', true);
		$this->addComponent($this->bg);
		
		$this->title = new Elements\Label(self::SIZE_X);
		$this->title->setAlign('center');
		$this->title->setPosition(self::SIZE_X/2, -6);
		$this->title->setStyle(Elements\Label::TextRaceMessage);
		$this->title->setOpacity(0.9);
		$this->title->setTextid('players');
		$this->addComponent($this->title);

//		$this->frame = new \ManiaLive\Gui\Controls\Frame(2.2,-15, new \ManiaLib\Gui\Layouts\Column());
//		$this->frame->getLayout()->setMarginHeight(0.5);
//		$this->addComponent($this->frame);
		
		$this->pager = new \ManiaLive\Gui\Controls\Pager(); 
		$this->pager->setPosition(2.2,-15);
		$this->pager->setSize(40, 110); 
		$this->pager->pageNavigatorFrame->setPosition(5,5);
		$this->pager->label->setTextColor('fff');
		
		$this->addComponent($this->pager);
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
		\ManiaLive\Gui\Manialinks::appendXML(\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::getInstance()->getManiaLink(array('players' => 'players')));
	}

	protected function updateItemList()
	{
		$this->pager->clearItems();
		//$this->frame->clearComponents();
		
		uasort(self::$playerList,
			function ($p1, $p2)
			{
				if($p1['state'] == $p2['state'])
				{
					if($p1['ladderPoints'] == $p2['ladderPoints'])
					{
						return 0;
					}
					return $p1['ladderPoints'] > $p2['ladderPoints'] ? -1 : 1;
				}
				return $p1['state'] > $p2['state'] ? -1 : 1;
			}
		);
		
		$count = 0;
		reset(self::$playerList);
		while(current(self::$playerList) && $count++ < 52)
		{
			$player = current(self::$playerList);

			if($this->smallCards)
			{
				$component = new PlayerSmall($player['nickname']);
			}
			else
			{
				$component = new Player($player['nickname']);
			}
			$component->state = $player['state'];
			$component->ladderPoints = $player['ladderPoints'];
			$component->zoneFlagURL = $player['zoneFlag'];
			$this->pager->addItem($component);
			//$this->frame->addComponent(clone $component);
			next(self::$playerList);
		}
	}


}

?>
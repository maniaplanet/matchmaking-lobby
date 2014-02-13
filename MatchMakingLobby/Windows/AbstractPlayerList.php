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

abstract class AbstractPlayerList extends \ManiaLive\Gui\Window
{
	const SIZE_X = 52;
	const SIZE_Y = 127;
	
	public $smallCards = false;
	
	static protected $playerList = array();
	
	protected $orderList = true;
	
	/**
	 * @var Elements\Quad 
	 */
	public $bg;
	
	/**
	 * @var Elements\Label 
	 */
	public $title;

	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $frame;
	
	/**
	 * @var \ManiaLive\Gui\Controls\Pager 
	 */
	protected $pager;
	
	protected $dictionnary = array();

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
		$this->title->setTextid('title');
		$this->addComponent($this->title);

		$this->frame = new \ManiaLive\Gui\Controls\Frame(2.2,-15, new \ManiaLib\Gui\Layouts\Column());
		$this->frame->getLayout()->setMarginHeight(0.5);
		$this->addComponent($this->frame);
		
		// $this->pager = new \ManiaLive\Gui\Controls\Pager(); 
		// $this->pager->setPosition(2.2,-15);
		// $this->pager->setSize(40, 110); 
		// $this->pager->pageNavigatorFrame->setPosition(5,5);
		// $this->pager->label->setTextColor('fff');
		// $this->addComponent($this->pager);
	}
	
	static function addPlayer($login, $nickName = null, $ladderPoints = 0, $state = 0, $uniqLogin = true)
	{
		$storage = \ManiaLive\Data\Storage::getInstance();
		try
		{
			$player = array(
				'login' => $login,
				'nickname' => $nickName ?  : $login,
				'ladderPoints' => $ladderPoints,
				'state' => $state,
				);
			
			if ($uniqLogin)
			{
				static::$playerList[$login] = $player;
			}
			else
			{
				array_unshift(static::$playerList, $player);
			}
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
			unset(static::$playerList[$login]);
		}
	}
	
	static function setPlayer($login, $nickName, $ladderPoints, $state)
	{
		static::addPlayer($login, $nickName, $ladderPoints, $state);
	}
	
	protected function updateItemList()
	{
		//$this->pager->clearItems();
		$this->frame->destroyComponents();
		
		if ($this->orderList)
		{
			uasort(static::$playerList,
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
		}
		
		$count = 0;
		reset(static::$playerList);
		while(current(static::$playerList) && $count++ < 18)
		{
			$player = current(static::$playerList);

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
			$component->zoneFlagURL = $flagURL = sprintf('file://ZoneFlags/Login/%s/country', $player['login']);;
			//$this->pager->addItem($component);
			$this->frame->addComponent($component);
			next(static::$playerList);
		}
	}
	
	function onDraw()
	{
		$this->setPosZ(3);
		\ManiaLive\Gui\Manialinks::appendXML(\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::getInstance()->getManiaLink($this->dictionnary));
		$this->updateItemList();
	}
	
	function destroy()
	{
		$this->destroyComponents();
		parent::destroy();
	}
}

?>
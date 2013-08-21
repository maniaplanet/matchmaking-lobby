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

class PlayerList extends \ManiaLive\Gui\Window
{
	const SIZE_X = 52;
	const SIZE_Y = 127;
	
	protected static $displayAlliesHelp = true;
	
	protected $playerList = array();
	
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
	 *
	 * @var Elements\Label
	 */
	public $alliesHelp;

	/**
	 * @var \ManiaLive\Gui\Controls\Pager 
	 */
	protected $pager;
	
	protected $dictionnary = array();
	
	static function setDisplayAlliesHelp($display = true)
	{
		static::$displayAlliesHelp = $display;
	}

	protected function onConstruct()
	{
		$this->setLayer(\ManiaLive\Gui\Window::LAYER_CUT_SCENE);
		
		$this->setSize(self::SIZE_X, self::SIZE_Y);
		
		$this->setAlign('right', 'center');
		
		//162
		$this->setPosition(163, 0, 15);
		
		$this->bg = new Elements\Quad(self::SIZE_X, self::SIZE_Y);
		$this->bg->setImage('file://Media/Manialinks/Common/Lobbies/side-frame.png', true);
		$this->addComponent($this->bg);
		
		$this->title = new Elements\Label(self::SIZE_X);
		$this->title->setAlign('center');
		$this->title->setPosition(self::SIZE_X/2, -4.5);
		$this->title->setStyle(Elements\Label::TextRaceMessage);
		$this->title->setOpacity(0.9);
		$this->title->setTextid('title');
		$this->addComponent($this->title);
		
		$this->alliesHelp = new Elements\Label(self::SIZE_X - 6);
		$this->alliesHelp->setAlign('center', 'bottom');
		$this->alliesHelp->setPosition(self::SIZE_X/2, -13);
		$this->alliesHelp->setStyle(Elements\Label::TextTips);
		$this->alliesHelp->setTextid('help');
		$this->alliesHelp->setTextSize(1);
		$this->alliesHelp->setOpacity(0.75);
		$this->addComponent($this->alliesHelp);
		
		$this->pager = new \ManiaLive\Gui\Controls\Pager(); 
		$this->pager->setPosition(2.2,-15);
		$this->pager->setSize(40, 110); 
		$this->pager->pageNavigatorFrame->setPosition(5,5);
		$this->pager->label->setTextColor('fff');
		$this->addComponent($this->pager);
		
		$ui = new Elements\Entry();
		$ui->setName('allyLogin');
		$ui->setId('allyLogin_entry');
		$ui->setHidden(true);
		$this->addComponent($ui);
		
		$this->dictionnary['title'] = 'players';
		$this->dictionnary['help'] = 'alliesHelp';
	}
	
	function addPlayer($login, $nickName = null, $ladderPoints = 0, $state = 0, $action = null, $isAlly = false, $isBilateral = false)
	{
		try
		{
			$player = array(
				'login' => $login,
				'nickname' => $nickName ?  : $login,
				'ladderPoints' => $ladderPoints,
				'state' => $state,
				'action' => $action,
				'isAlly' => $isAlly,
				'isBilateral' => $isBilateral
				);
			
			$this->playerList[$login] = $player;
		}
		catch(\Exception $e)
		{
			return;
		}
	}

	function removePlayer($login)
	{
		if(array_key_exists($login, $this->playerList))
		{
			unset($this->playerList[$login]);
		}
	}
	
	function setPlayer($login, $nickName, $ladderPoints, $state, $action, $isAlly, $isBilateral)
	{
		$this->addPlayer($login, $nickName, $ladderPoints, $state, $action, $isAlly, $isBilateral);
	}
	
	protected function updateItemList()
	{
		$this->pager->clearItems();
//		$this->pager->clearComponents();
		
		if ($this->orderList)
		{
			uasort($this->playerList,
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
		reset($this->playerList);
		while(current($this->playerList) && $count++ < 52)
		{
			$player = current($this->playerList);

			$component = new Player($player['nickname']);
			$component->state = $player['state'];
			$component->ladderPoints = $player['ladderPoints'];
			$component->zoneFlagURL = $flagURL = sprintf('file://ZoneFlags/Login/%s/country', $player['login']);
			$component->login = $player['login'];
			$component->setAction($player['action']);
			$component->setId($count);
			
			if($player['isAlly'] && $player['isBilateral'])
			{
				$component->setBackgroundColor('07C8','09F8');
			}
			elseif($player['isAlly'] && !$player['isBilateral'])
			{
				$component->setBackgroundColor('9998','EEE8');
			}
			elseif($player['action'])
			{
				$component->setBackgroundColor('3338','CCC8');
			}
			else
			{
				$component->setBackgroundColor();
			}
			$this->pager->addItem($component);
			next($this->playerList);
		}
	}
	
	function onDraw()
	{
		$this->setPosZ(3);
		if(static::$displayAlliesHelp)
		{
			$this->title->setPosY(-4.5);
			$this->alliesHelp->setVisibility(true);
		}
		else
		{
			$this->title->setPosY(-6);
			$this->alliesHelp->setVisibility(false);
		}
		\ManiaLive\Gui\Manialinks::appendXML(\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::getInstance()->getManiaLink($this->dictionnary));
		\ManiaLive\Gui\Manialinks::appendScript(
<<<EOSCRIPT
#RequireContext CMlScript
#Include "MathLib" as MathLib
#Include "TextLib" as TextLib

main()
{
	declare Text ClickedIndex;
	declare Text ButtonIdPrefix;
	declare Text DataIdPrefix;
	declare Text DataEntryId;
	declare Integer ButtonIdPrefixLength;
	declare CMlLabel DataLabel;
	declare CMlEntry DataEntry;
		
	ButtonIdPrefix = "player_button-";
	DataIdPrefix = "player_label-";
	DataEntryId = "allyLogin_entry";
	
	ButtonIdPrefixLength = TextLib::Length(ButtonIdPrefix);
	DataEntry <=> (Page.GetFirstChild(DataEntryId) as CMlEntry);

	while(True)
	{
		foreach(Event in PendingEvents)
		{
			if(Event.Type == CMlEvent::Type::MouseOver)
			{
				if(TextLib::SubString(Event.ControlId, 0, ButtonIdPrefixLength) == ButtonIdPrefix)
				{
					ClickedIndex = TextLib::SubString(Event.ControlId, ButtonIdPrefixLength, 2);
					DataLabel <=> (Page.GetFirstChild(DataIdPrefix^ClickedIndex) as CMlLabel);
					if(DataLabel != Null)	
					{
						DataEntry.Value = DataLabel.Value;
					}
				}
			}
		}
		yield;
	}
}
EOSCRIPT
			);
		
		$this->updateItemList();
	}
}

?>
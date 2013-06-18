<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9091 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-12 16:37:36 +0100 (mer., 12 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Controls;

use ManiaLib\Gui\Elements;

class Player extends \ManiaLive\Gui\Control
{
	const STATE_BLOCKED = -2;
	const STATE_NOT_READY = -1;
	const STATE_IN_MATCH = 1;
	const STATE_READY = 2;

	public $state;
	public $isAlly = false;
	public $login;
	public $nickname;
	public $ladderPoints;
	public $zoneFlagURL;

	protected $bg;
	
	/**
	 * @var Elements\Icons64x64_1
	 */
	protected $icon;

	/**
	 * @var Elements\Label
	 */
	protected $label;

	/**
	 * @var Elements\Label
	 */
	protected $rankLabel;
	
	/**
	 * @var Elements\Quad
	 */
	protected $countryFlag;

	function __construct($nickname)
	{
		$this->setSize(80, 5);

		$this->bg = new Elements\Bgs1InRace($this->sizeX, $this->sizeY);
		$this->bg->setSubStyle(Elements\Bgs1InRace::BgListLine);
//		$this->bg->setBgcolor('222');
		$this->addComponent($this->bg);

		$this->icon = new Elements\Icons64x64_1(2.5, 2.5);
		$this->icon->setSubStyle(Elements\Icons64x64_1::LvlRed);
		$this->icon->setAlign('right','center');
		$this->addComponent($this->icon);

		$this->label = new Elements\Label(34);
		$this->label->setValign('center2');
		$this->label->setText($nickname);
		$this->label->setTextColor('fff');
		$this->label->setTextSize(1);
		$this->addComponent($this->label);

		$this->rankLabel = new Elements\Label(15);
		$this->rankLabel->setAlign('right','center2');
		$this->rankLabel->setText('-');
		$this->rankLabel->setTextColor('fff');
		$this->rankLabel->setTextPrefix('$o$s');
		$this->rankLabel->setTextSize(1);
		$this->addComponent($this->rankLabel);
		
		$this->countryFlag = new Elements\Quad(6.7, 5);
		$this->countryFlag->setAlign('left','center');
		$this->addComponent($this->countryFlag);

		$this->nickname = $nickname;
		$this->state = static::STATE_NOT_READY;
	}
	
	function onDraw()
	{
		switch($this->state)
		{
			case static::STATE_READY:
				$subStyle = Elements\Icons64x64_1::LvlGreen;
				break;
			case static::STATE_IN_MATCH:
				$subStyle = Elements\Icons64x64_1::LvlYellow;
				break;
			case static::STATE_BLOCKED:
				$subStyle = Elements\Icons64x64_1::StatePrivate;
				break;
			case static::STATE_NOT_READY:
				//nobreak
			default :
				$subStyle = Elements\Icons64x64_1::LvlRed;
		}
		
		$this->icon->setPosition($this->sizeX - 6, - $this->sizeY / 2);
		$this->label->setPosition(7.5, - $this->sizeY / 2);
		$this->rankLabel->setPosition($this->sizeX - 1, - $this->sizeY / 2);
		$this->countryFlag->setPosition(0, - $this->sizeY / 2);
		$this->bg->setSize($this->sizeX, $this->sizeY);
		
		$this->icon->setSubStyle($subStyle);
		$this->countryFlag->setImage($this->zoneFlagURL, true);
		$this->rankLabel->setText($this->ladderPoints > 0 ? floor($this->ladderPoints /10000) : '-');
	}
}

?>
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
		$this->setSize(70, 5);

		$ui = new Elements\Bgs1InRace(70, 5);
//		$ui->setSubStyle(Elements\Bgs1InRace::BgListLine);
		$ui->setBgcolor('222');
		$this->addComponent($ui);

		$this->icon = new Elements\Icons64x64_1(2.5, 2.5);
		$this->icon->setSubStyle(Elements\Icons64x64_1::LvlRed);
		$this->icon->setAlign('right','center');
		$this->icon->setPosition(63, -2.5);
		$this->addComponent($this->icon);

		$this->label = new Elements\Label(34);
		$this->label->setValign('center2');
		$this->label->setPosition(7.5, -2.5);
		$this->label->setText($nickname);
		$this->label->setTextColor('fff');
		$this->label->setScale(0.75);
		$this->addComponent($this->label);

		$this->rankLabel = new Elements\Label(15);
		$this->rankLabel->setAlign('right','center2');
		$this->rankLabel->setPosition(69, -2.5);
		$this->rankLabel->setText('-');
		$this->rankLabel->setTextColor('fff');
		$this->rankLabel->setTextSize(1);
		$this->rankLabel->setScale(0.6);
		$this->addComponent($this->rankLabel);
		
		$this->countryFlag = new Elements\Quad(4, 3);
		$this->countryFlag->setAlign('left','center');
		$this->countryFlag->setPosition(1, -2.5);
		$this->addComponent($this->countryFlag);

		$this->nickname = $nickname;
		$this->state = static::STATE_NOT_READY;
	}

	function setState($state = 1, $zone = 'World', $ladderPoints = -1)
	{
		switch($state)
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
		$this->state = $state;
		$this->ladderPoints = $ladderPoints;

		$this->icon->setSubStyle($subStyle);
		$this->countryFlag->setImage('http://www.pepinieresbonnetfreres.be/Flags/france-flag.jpg', true);
		$this->rankLabel->setText($ladderPoints > 0 ? (int)$ladderPoints : '-');
	}
}

?>
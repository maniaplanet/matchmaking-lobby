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

	/**
	 * @var Elements\Icons64x64_1
	 */
	protected $icon;

	/**
	 * @var Elements\Label
	 */
	protected $label;

	function __construct($nickname)
	{
		$this->setSize(50, 5);
		$storage = \ManiaLive\Data\Storage::getInstance();

		$ui = new Elements\Bgs1InRace(50, 5);
		$ui->setSubStyle(Elements\Bgs1InRace::BgListLine);
		$this->addComponent($ui);

		$this->icon = new Elements\Icons64x64_1(2.5, 2.5);
		$this->icon->setSubStyle(Elements\Icons64x64_1::LvlRed);
		$this->icon->setValign('center');
		$this->icon->setPosition(1, -2.5);
		$this->addComponent($this->icon);

		$this->label = new Elements\Label(30);
		$this->label->setValign('center2');
		$this->label->setPosition(5, -2.5);
		$this->label->setText($nickname);
		$this->label->setTextColor('fff');
		$this->label->setScale(0.75);
		$this->addComponent($this->label);
	}

	function setState($state = 1)
	{
		switch($state)
		{
			case 1:
				$subStyle = Elements\Icons64x64_1::LvlGreen;
				break;
			case 2:
				$subStyle = Elements\Icons64x64_1::LvlYellow;
				break;
			case 3:
				$subStyle = Elements\Icons64x64_1::StatePrivate;
				break;
			default:
				$subStyle = Elements\Icons64x64_1::LvlRed;
		}
		$this->icon->setSubStyle($subStyle);
	}

}

?>
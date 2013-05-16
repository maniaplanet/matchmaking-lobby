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
	public $nickname;
	public $rank;

	/**
	 * @var Elements\Icons64x64_1
	 */
	protected $icon;
	/**
	 * @var Elements\Icons64x64_1
	 */
	protected $allyIcon;

	/**
	 * @var Elements\Label
	 */
	protected $label;

	/**
	 *
	 * @var Elements\Label
	 */
	protected $rankLabel;

	function __construct($nickname)
	{
		$this->setSize(50, 5);

		$ui = new Elements\Bgs1InRace(50, 5);
		$ui->setSubStyle(Elements\Bgs1InRace::BgListLine);
		$this->addComponent($ui);

		$this->icon = new Elements\Icons64x64_1(2.5, 2.5);
		$this->icon->setSubStyle(Elements\Icons64x64_1::LvlRed);
		$this->icon->setValign('center');
		$this->icon->setPosition(1, -2.5);
		$this->addComponent($this->icon);

		$this->allyIcon = new Elements\Icons64x64_1(2.5, 2.5);
		$this->allyIcon->setSubStyle(Elements\Icons64x64_1::Buddy);
		$this->allyIcon->setValign('center');
		$this->allyIcon->setPosition(4, -2.5);
		$this->addComponent($this->allyIcon);

		$this->label = new Elements\Label(34);
		$this->label->setValign('center2');
		$this->label->setPosition(7.5, -2.5);
		$this->label->setText($nickname);
		$this->label->setTextColor('fff');
		$this->label->setScale(0.75);
		$this->addComponent($this->label);

		$this->rankLabel = new Elements\Label(15);
		$this->rankLabel->setAlign('right','center2');
		$this->rankLabel->setPosition(43, -2.5);
		$this->rankLabel->setText(sprintf('World %7s',($this->rank > 0 ? $this->rank : '-')));
		$this->rankLabel->setTextColor('fff');
		$this->rankLabel->setTextSize(1);
		$this->rankLabel->setScale(0.6);
		$this->addComponent($this->rankLabel);

		$this->nickname = $nickname;
		$this->state = static::STATE_NOT_READY;
	}

	function setState($state = 1, $isAlly = false, $rank = 0, $zone = 'World')
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
				$subStyle = Elements\Icons64x64_1::LvlRed;
				break;
			default :
				$subStyle = Elements\Icons64x64_1::LvlRed;
		}
		$this->state = $state;
		$this->isAlly = $isAlly;
		$this->rank = $rank;

		$this->icon->setSubStyle($subStyle);
		$this->allyIcon->setSubStyle($isAlly ? Elements\Icons64x64_1::Buddy : Elements\Icons64x64_1::EmptyIcon);
		$this->rankLabel->setText(sprintf('%s %-7s',$zone, ($this->rank > 0 ? $this->rank : '-')));
	}
}

?>
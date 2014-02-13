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
use ManiaLivePlugins\MatchMakingLobby\Services\PlayerInfo;

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
	protected $hiddenLabel;

	/**
	 * @var Elements\Label
	 */
	protected $echelonLabel;

	/**
	 * @var Elements\Quad
	 */
	protected $echelonQuad;

	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $echelonFrame;

	/**
	 * @var Elements\Quad
	 */
	protected $countryFlag;

	function __construct($nickname)
	{
		$this->setSize(46, 5);

		$this->bg = new Elements\Bgs1InRace($this->sizeX, $this->sizeY);
		$this->bg->setBgcolor('3338');
		$this->bg->setBgcolorFocus('CCC8');
		$this->bg->setScriptEvents();
		$this->addComponent($this->bg);

		$this->icon = new Elements\Quad(2, $this->sizeY);
		$this->icon->setBgcolor('F00');
		$this->icon->setAlign('left','center');
		$this->addComponent($this->icon);

		$this->label = new Elements\Label(34);
		$this->label->setValign('center2');
		$this->label->setText($nickname);
		$this->label->setTextColor('fff');
		$this->label->setTextSize(1);
		$this->addComponent($this->label);

		$this->hiddenLabel = new Elements\Label();
		$this->hiddenLabel->setHidden(true);
		$this->addComponent($this->hiddenLabel);

		$this->echelonFrame = new \ManiaLive\Gui\Controls\Frame(73.5, 1);
		$this->echelonFrame->setScale(0.29);
		$this->addComponent($this->echelonFrame);

		$this->echelonQuad = new Elements\Quad(14.1551, 17.6938);
		$this->echelonQuad->setPosition(-1.25, -1.25);
		$this->echelonQuad->setAlign('center', 'top');
		$this->echelonFrame->addComponent($this->echelonQuad);

		$ui = new Elements\Label(15);
		$ui->setAlign('center', 'top');
		$ui->setStyle(Elements\Label::TextRaceMessage);
		$ui->setPosition(-1, -4.95625);
		$ui->setTextSize(0.5);
		$ui->setText('Echelon');
		$this->echelonFrame->addComponent($ui);

		$this->echelonLabel = new Elements\Label(10, 10);
		$this->echelonLabel->setAlign('center','center');
		$this->echelonLabel->setPosition(-1, -11.895);
		$this->echelonLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->echelonFrame->addComponent($this->echelonLabel);

		$this->countryFlag = new Elements\Quad(5, 5);
		$this->countryFlag->setAlign('left','center');
		$this->addComponent($this->countryFlag);

		$this->nickname = $nickname;
		$this->state = static::STATE_NOT_READY;
	}

	function setId($id)
	{
		$this->bg->setId('player_button-'.$id);
		$this->hiddenLabel->setId('player_label-'.$id);
	}

	function onDraw()
	{
		switch($this->state)
		{
			case static::STATE_READY:
				$subStyle = '0F0D';
				break;
			case static::STATE_IN_MATCH:
				$subStyle = 'FF0D';
				break;
			case static::STATE_BLOCKED:
				$subStyle = '000D';
				break;
			case static::STATE_NOT_READY:
				$subStyle = 'F00D';
				break;
			default :
				$subStyle = '';
		}

		$this->icon->setSize(1, $this->sizeY);
		$this->icon->setPosition(0, - $this->sizeY / 2);
		$this->label->setPosition(8, - $this->sizeY / 2);
		$this->echelonFrame->setPosition($this->sizeX - 1, 0.5);
		$this->countryFlag->setPosition(2, - $this->sizeY / 2);
		$this->bg->setSize($this->sizeX, $this->sizeY);
		$this->hiddenLabel->setText($this->login);

		$echelon = PlayerInfo::ComputeEchelon($this->ladderPoints);
		$this->icon->setBgcolor($subStyle);
		$this->countryFlag->setImage($this->zoneFlagURL, true);
		$this->echelonLabel->setText($echelon);
		$this->echelonQuad->setImage(sprintf('file://Media/Manialinks/Common/Echelons/echelon%d.dds',$echelon), true);
	}

	function destroy()
	{
		$this->destroyComponents();
		
		parent::destroy();
	}
	function onIsRemoved(\ManiaLive\Gui\Container $target)
	{
		parent::onIsRemoved($target);
		$this->destroy();
	}

	function setAction($action)
	{
		$this->bg->setAction($action);
	}

	function setBackgroundColor($color = '3338', $focusColor = null)
	{
		$this->bg->setBgcolor($color);
		$this->bg->setBgcolorFocus($focusColor);
	}
}

?>
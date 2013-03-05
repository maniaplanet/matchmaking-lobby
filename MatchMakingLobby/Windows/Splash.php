<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9066 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-07 15:12:49 +0100 (ven., 07 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Button;
use ManiaLib\Gui\Elements\Label as LabelElement;
use ManiaLive\Gui\Controls\Frame;

class Splash extends \ManiaLive\Gui\Window
{

	/** @var LabelElement */
	private $title;

	/** @var Frame */
	private $linesFrame;
	
	/**
	 * @var Button
	 */
	private $button1;
	
	private $button2;

	protected function onConstruct()
	{
		$this->setSize(120, 60);
		$this->centerOnScreen();

		$ui = new Bgs1InRace(120, 60);
		$ui->setSubStyle(Bgs1InRace::BgWindow2);
		$this->addComponent($ui);
		// Twice because of opacity
		$ui = clone $ui;
		$this->addComponent($ui);

		$this->title = new LabelElement(100);
		$this->title->setStyle(LabelElement::TextCardMedium);
		$this->title->setPosition(60, -2);
		$this->title->setHalign('center');
		$this->addComponent($this->title);

		$this->linesFrame = new Frame(4, -14, new \ManiaLib\Gui\Layouts\Column());
		$this->addComponent($this->linesFrame);

		$this->button1 = new Button();
		$this->button1->setAlign('right', 'bottom');
		$this->button1->setPosition(58, -58);
		$this->button1->setText('Do not show again');
		$this->addComponent($this->button1);
		
		$this->button2 = new Button();
		$this->button2->setAlign('left', 'bottom');
		$this->button2->setPosition(62, -58);
		$this->button2->setText('Close');
		$this->addComponent($this->button2);
	}

	function set($title,array $lines, $action1, $action2)
	{
		$this->title->setText($title);

		$this->linesFrame->clearComponents();
		$this->button1->setAction($action1);
		$this->button2->setAction($action2);
		$bullet = '$<$o$0af+$>$555 ';
		foreach($lines as $line)
		{
			$ui = new LabelElement(112, 5);
			$ui->setStyle(LabelElement::TextCardSmallScores2);
			$ui->setText($bullet.$line);
			$this->linesFrame->addComponent($ui);
		}
	}

}

?>
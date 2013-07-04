<?php

/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

class Dialog extends \ManiaLive\Gui\Window
{
	protected $questionText;
	protected $yesAction;
	protected $noAction;
	
	/**
	 * @var Elements\Label
	 */
	protected $questionLabel;
	
	/**
	 * @var Elements\Button
	 */
	protected $yesButton;
	
	/**
	 * @var Elements\Button
	 */
	protected $noButton;
		
	function onConstruct()
	{
		$ui = new Elements\Bgs1(320, 180);
		$ui->setAlign('center','center');
		$ui->setSubStyle(Elements\Bgs1::BgDialogBlur);
		$this->addComponent($ui);
		
		$ui = new Elements\Bgs1(163, 63);
		$ui->setAlign('center','center');
		$ui->setSubStyle(Elements\Bgs1::BgShadow);
		$this->addComponent($ui);
		
		$ui = new Elements\Quad(160, 60);
		$ui->setAlign('center','center');
		$ui->setBgcolor('eeef');
		$this->addComponent($ui);
		
		$this->questionLabel = new Elements\Label(160);
		$this->questionLabel->setAlign('center','center2');
		$this->questionLabel->setPosY(5);
		$this->questionLabel->enableAutonewline();
		$this->questionLabel->setStyle(Elements\Label::TextInfoMedium);
		$this->addComponent($this->questionLabel);
		
		$this->yesButton = new Elements\Button();
		$this->yesButton->setAlign('left','bottom');
		$this->yesButton->setPosition(-45, -25);
		$this->yesButton->setTextid('yes');
		$this->addComponent($this->yesButton);
		
		$this->noButton = new Elements\Button();
		$this->noButton->setAlign('right', 'bottom');
		$this->noButton->setPosition(45, -25);
		$this->noButton->setTextid('no');
		$this->addComponent($this->noButton);
	}
	
	function setQuestionText($text)
	{
		$this->questionText = $text;
	}
	
	function setYesAction($action)
	{
		$this->yesAction = $action;
	}
	
	function setNoAction($action)
	{
		$this->noAction = $action;
	}
	
	function onDraw()
	{
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink(array(
				'yes' => 'yes',
				'no' => 'no',
		)));
		$this->yesButton->setAction($this->yesAction);
		$this->noButton->setAction($this->noAction);
		$this->questionLabel->setText($this->questionText);
	}
}

?>

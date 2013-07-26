<?php

/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

class AlertReady extends Alert
{
	public $yesAction;
	public $yesButton;
	
	public $noAction;
	public $noButton;
	
	function onConstruct()
	{
		parent::onConstruct();
		
		$ui = new Elements\Quad(static::SIZE_X, static::SIZE_Y);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/2013-07-26/limited-games-bg.png', true);
		$ui->setAlign('center','center');
		$this->addComponent($ui);
		
		$ui = new Elements\Label(static::SIZE_X-40, 10);
		$ui->setStyle(Elements\Label::TextRaceMessage);
		$ui->setTextPrefix('$i$o');
		$ui->setOpacity(0.8);
		$ui->setTextSize(2);
		$ui->setAlign('center', 'top');
		$ui->setPosition(0, 5);
		$ui->setTextid('demoReady');
		$ui->enableAutonewline();
		$this->frameContent->add($ui);
		
		$ui = new Elements\Spacer(0,10);
		$this->frameContent->add($ui);
		
		$ui = new Elements\Label(static::SIZE_X-40, 10);
		$ui->setStyle(Elements\Label::TextRaceMessage);
		$ui->setTextPrefix('$i$o');
		$ui->setOpacity(0.5);
		$ui->setTextSize(2);
		$ui->setAlign('center', 'top');
		$ui->setPosition(0, 5);
		$ui->setTextid('demoReady2');
		$ui->enableAutonewline();
		$ui->setTextEmboss();
		$this->frameContent->add($ui);
		
		$this->yesButton = new \ManiaLivePlugins\MatchMakingLobby\Controls\ButtonImage(50, 10);
		$this->yesButton->setPosition(-30, -5);
		$this->yesButton->bg->setImage('http://static.maniaplanet.com/manialinks/lobbies/2013-07-26/large-button-GREEN-OFF.png', true);
		$this->yesButton->bg->setImageFocus('http://static.maniaplanet.com/manialinks/lobbies/2013-07-26/large-button-GREEN-ON.png', true);
		$this->yesButton->text->setTextid('ok');
		$this->addComponent($this->yesButton);
		
		$this->noButton = new \ManiaLivePlugins\MatchMakingLobby\Controls\ButtonImage(50, 10);
		$this->noButton->setPosition(30, -5);
		$this->noButton->bg->setImage('http://static.maniaplanet.com/manialinks/lobbies/2013-07-26/large-button-RED-OFF.png', true);
		$this->noButton->bg->setImageFocus('http://static.maniaplanet.com/manialinks/lobbies/2013-07-26/large-button-RED-ON.png', true);
		$this->noButton->text->setTextid('cancel');
		$this->addComponent($this->noButton);
		
		$this->frameContent->add(new Elements\Spacer(0,14));
		
		$ui = new Elements\Label(static::SIZE_X-10, 10);
		$ui->setStyle(Elements\Label::TextRaceMessage);
		$ui->setTextSize(2);
		$ui->setAlign('center', 'top');
		$ui->setTextid('demoReadyTips');
		$ui->setTextPrefix('$i');
		$ui->setOpacity(0.6);
		$ui->enableAutonewline();
		$this->frameContent->add($ui);
	}
	
	function onDraw()
	{
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink(array(
				'demoReady' => 'demoReady',
				'demoReady2' => 'demoReady2',
				'demoReadyTips' => 'demoReadyTips',
				'ok' => 'yes',
				'cancel' => 'no',
		)));
		
		$this->yesButton->bg->setAction($this->yesAction);
		$this->noButton->bg->setAction($this->noAction);
	}
}

?>

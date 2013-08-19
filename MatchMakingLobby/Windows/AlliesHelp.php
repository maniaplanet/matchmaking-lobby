<?php

/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

class AlliesHelp extends Alert
{
	public $button;
	
	public $action;
	
	function onConstruct()
	{
		
		$ui = new Elements\Quad(static::SIZE_X, static::SIZE_Y);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/2013-07-26/limited-games-bg.png', true);
		$ui->setAlign('center','center');
		$this->addComponent($ui);
		
		parent::onConstruct();
		
		$ui = new Elements\Label(static::SIZE_X-40, 10);
		$ui->setStyle(Elements\Label::TextRaceMessage);
		$ui->setTextPrefix('$i$o');
		$ui->setOpacity(0.8);
		$ui->setTextSize(2);
		$ui->setAlign('center', 'top');
		$ui->setPosition(0, 5);
		$ui->setTextid('text');
		$ui->enableAutonewline();
		$this->frameContent->addComponent($ui);
		
		$ui = new Elements\Spacer(0,20);
		$this->frameContent->addComponent($ui);
		
		$this->button = new \ManiaLivePlugins\MatchMakingLobby\Controls\ButtonImage(50, 10);
		$this->button->setPosition(0, -5);
		$this->button->bg->setImage('http://static.maniaplanet.com/manialinks/lobbies/2013-07-26/large-button-GREEN-OFF.png', true);
		$this->button->bg->setImageFocus('http://static.maniaplanet.com/manialinks/lobbies/2013-07-26/large-button-GREEN-ON.png', true);
		$this->button->text->setTextid('ok');
		$this->addComponent($this->button);
		
		
		$this->frameContent->addComponent(new Elements\Spacer(0,14));
		
		$ui = new Elements\Label(static::SIZE_X-10, 10);
		$ui->setStyle(Elements\Label::TextRaceMessage);
		$ui->setTextSize(2);
		$ui->setAlign('center', 'top');
		$ui->setTextid('tips');
		$ui->setTextPrefix('$i');
		$ui->setOpacity(0.6);
		$ui->enableAutonewline();
		$this->frameContent->addComponent($ui);
	}
	
	function onDraw()
	{
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink(array(
				'text' => 'alliesHelpText',
				'tips' => 'alliesHelpTips',
				'ok' => 'ok',
		)));
		
		$this->button->bg->setAction($this->action);
	}
}

?>

<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

class WaitingScreen extends \ManiaLive\Gui\Window
{

	/**
	 * @var int
	 */
	static public $playingCount = 0;

	/**
	 * @var int
	 */
	static public $waitingCount = 0;

	/**
	 * @var double
	 */
	static public $avgWaitTime = -1;

	/**
	 * @var string
	 */
	static public $serverName = '';
	
	static public $readyAction;

	/**
	 * @var Elements\Label
	 */
	protected $serverNameLabel;

	/**
	 * @var Elements\Label
	 */
	protected $playingCountLabel;

	/**
	 * @var Elements\Label
	 */
	protected $waitingCountLabel;

	/**
	 * @var Elements\Label
	 */
	protected $avgWaitTimeLabel;
	
	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $buttonFrame;
	
	/**
	 * @var Elements\Button
	 */
	protected $readyButton;
		
	function onConstruct()
	{
		$ui = new Elements\Bgs1(320, 128);
		$ui->setAlign('center', 'center');
		$ui->setSubStyle(Elements\Bgs1::BgDialogBlur);
		$this->addComponent($ui);
		
		$ui = new Elements\Quad(320, 142);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://127.0.0.1/lobby-background.png',true);
		$this->addComponent($ui);

		$this->serverNameLabel = new Elements\Label(100, 20);
		$this->serverNameLabel->setAlign('center', 'center');
		$this->serverNameLabel->setPosY(62.5);
		$this->serverNameLabel->setStyle(Elements\Label::TextTitle1);
		$this->serverNameLabel->setTextEmboss();
		$this->addComponent($this->serverNameLabel);

		$ui = new Elements\Bgs1InRace(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setPosition(-95, 50);
		$ui->setSubStyle(Elements\Bgs1InRace::BgTitle3_5);
		$this->addComponent($ui);

		// TODO Add to Translation files
		$ui = new Elements\Label(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setPosition(-95, 50);
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setTextEmboss();
		$ui->setText('Players');
		$this->addComponent($ui);

		$ui = new Elements\Bgs1InRace(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setPosition(95, 50);
		$ui->setSubStyle(Elements\Bgs1InRace::BgTitle3_5);
		$this->addComponent($ui);

		// TODO Add to Translation files
		$ui = new Elements\Label(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setPosition(95, 50);
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setTextEmboss();
		$ui->setText('Allies');
		$this->addComponent($ui);
		
		
		$ui = new Elements\Bgs1InRace(25, 15);
		$ui->setAlign('right', 'center');
		$ui->setPosition(-5, 35);
		$ui->setSubStyle(Elements\Bgs1InRace::BgTitle3_1);
		$this->addComponent($ui);
		
		$uiLabel = new Elements\Label(25);
		$uiLabel->setAlign('center');
		$uiLabel->setPosition(-17.5, 28);
		$uiLabel->setStyle(Elements\Label::TextButtonSmall);
		$uiLabel->setTextid('playing');
		$uiLabel->setTextSize(2);
		$this->addComponent($uiLabel);
		
		$this->playingCountLabel = new Elements\Label(25, 15);
		$this->playingCountLabel->setAlign('center', 'center2');
		$this->playingCountLabel->setPosition(-17.5, 35);
		$this->playingCountLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->playingCountLabel->setText('16');
		$this->playingCountLabel->setTextSize(7);
		$this->addComponent($this->playingCountLabel);
		
		$ui = clone $ui;
		$ui->setHalign('left');
		$ui->setPosX(5);
		$this->addComponent($ui);
		
		$uiLabel = clone $uiLabel;
		$uiLabel->setTextid('ready');
		$uiLabel->setPosition(17.5, 28);
		$this->addComponent($uiLabel);
		
		$this->waitingCountLabel = clone $this->playingCountLabel;
		$this->waitingCountLabel->setPosX(17.5);
		$this->waitingCountLabel->setText(32);
		$this->addComponent($this->waitingCountLabel);
		
		$ui = new Elements\Bgs1InRace(40, 15);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, 15);
		$ui->setSubStyle(Elements\Bgs1InRace::BgTitle3_1);
		$this->addComponent($ui);
		
		$ui = new Elements\Label(35,15);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, 7.5);
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setTextSize(2);
		$ui->setText('AVG Waiting Time');
		$this->addComponent($ui);
		
		$this->avgWaitTimeLabel = new Elements\Label(35, 15);
		$this->avgWaitTimeLabel->setAlign('center', 'center2');
		$this->avgWaitTimeLabel->setPosition(0, 15);
		$this->avgWaitTimeLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->avgWaitTimeLabel->setText('00:00');
		$this->avgWaitTimeLabel->setTextSize(7);
		$this->addComponent($this->avgWaitTimeLabel);
		
		$this->buttonFrame = new Frame();
		$this->buttonFrame->setLayout(new \ManiaLib\Gui\Layouts\Column(0, 0, \ManiaLib\Gui\Layouts\Column::DIRECTION_UP));
		$this->buttonFrame->getLayout()->setMarginHeight(3);
		$this->buttonFrame->setPosY(-60);
		$this->addComponent($this->buttonFrame);

		$ui = new Elements\Button();
		$ui->setHalign('center');
		$ui->setText('Back');
		$ui->setAction('0');
		$this->buttonFrame->addComponent($ui);
		
		$this->readyButton = new Elements\Button();
		$this->readyButton->setHalign('center');
		$this->readyButton->setText('Ready');
		$this->buttonFrame->addComponent($this->readyButton);
		
		$ui = new Elements\Button();
		$ui->setHalign('center');
		$ui->setText('Rules');
		$ui->setManialink('');
		$this->buttonFrame->addComponent($ui);
		
		$p = \ManiaLive\Data\Storage::getInstance()->getPlayerObject('satanasdiabolo');
		$ui = \ManiaLivePlugins\MatchMakingLobby\Controls\PlayerDetailed::fromPlayer($p);
		$this->buttonFrame->addComponent($ui);
	}

	function onDraw()
	{
		if(self::$avgWaitTime < 0)
		{
			$avgWaitingTime = '-';
		}
		else
		{
			$secs = ceil((self::$avgWaitTime - (int) self::$avgWaitTime) * 4) *15;
			$avgWaitingTime = sprintf('%1$02d:%2$02d',self::$avgWaitTime, $secs);
		}
		$this->serverNameLabel->setText(self::$serverName);
		$this->playingCountLabel->setText(self::$playingCount);
		$this->waitingCountLabel->setText(self::$waitingCount);
		$this->avgWaitTimeLabel->setText($avgWaitingTime);
		$this->readyButton->setAction(self::$readyAction);
		$this->posZ = 3.9;
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink(array('playing' => 'playing', 'ready'=>'ready')));
	}

}

?>

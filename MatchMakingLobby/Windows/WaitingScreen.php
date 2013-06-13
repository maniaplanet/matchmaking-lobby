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
		$ui = new Elements\Quad(320, 142);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://127.0.0.1/elements/lobby-background.png',true);
		$this->addComponent($ui);

		$this->serverNameLabel = new Elements\Label(100, 20);
		$this->serverNameLabel->setAlign('center', 'center');
		$this->serverNameLabel->setPosY(62.5);
		$this->serverNameLabel->setStyle(Elements\Label::TextTitle1);
		$this->serverNameLabel->setTextEmboss();
		$this->addComponent($this->serverNameLabel);
		
		$ui = new Elements\Label(100,40);
		$ui->setAlign('center');
		$ui->setPosY(45);
		$ui->setTextColor('fff');
		$ui->setTextSize(3);
		$ui->enableAutonewline();
		$ui->setText('Welcome to the matchmaking waiting room.
Press $oREADY$o to play, and wait your match.
Your Elite game will start automatically.');
		$this->addComponent($ui);

		$ui = new Elements\Bgs1InRace(40, 8);
		$ui->setAlign('center', 'center');
		$ui->setPosition(-95, 50);
		$ui->setImage('http://127.0.0.1/elements/grey-quad.png',true);
		$this->addComponent($ui);

		// TODO Add to Translation files
		$ui = new Elements\Label(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setPosition(-95, 50);
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setTextEmboss();
		$ui->setText('Players');
		$this->addComponent($ui);

		$ui = new Elements\Bgs1InRace(25, 12);
		$ui->setAlign('right', 'center');
		$ui->setPosition(-5, 15);
		$ui->setImage('http://127.0.0.1/elements/red-quad-small.png',true);
		$this->addComponent($ui);
		
		$uiLabel = new Elements\Label(25);
		$uiLabel->setAlign('center');
		$uiLabel->setPosition(-17.5, 8);
		$uiLabel->setStyle(Elements\Label::TextButtonSmall);
		$uiLabel->setTextid('playing');
		$uiLabel->setTextSize(2);
		$this->addComponent($uiLabel);
		
		$this->playingCountLabel = new Elements\Label(25, 15);
		$this->playingCountLabel->setAlign('center', 'center2');
		$this->playingCountLabel->setPosition(-17.5, 15);
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
		$uiLabel->setPosition(17.5, 8);
		$this->addComponent($uiLabel);
		
		$this->waitingCountLabel = clone $this->playingCountLabel;
		$this->waitingCountLabel->setPosX(17.5);
		$this->waitingCountLabel->setText(12);
		$this->addComponent($this->waitingCountLabel);
		
		$ui = new Elements\Bgs1InRace(40, 12);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, -5);
		$ui->setImage('http://127.0.0.1/elements/red-quad-wide.png',true);
		$this->addComponent($ui);
		
		$ui = new Elements\Label(35,15);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, -13.5);
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setTextSize(2);
		$ui->setText('AVG Waiting Time');
		$this->addComponent($ui);
		
		$this->avgWaitTimeLabel = new Elements\Label(35, 15);
		$this->avgWaitTimeLabel->setAlign('center', 'center2');
		$this->avgWaitTimeLabel->setPosition(0, -5);
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
		
		$frame = new Frame();
		$frame->setSize(50,10);
		$this->buttonFrame->addComponent($frame);
		
		$this->readyButton = new Elements\Quad(50,10);
		$this->readyButton->setHalign('center');
		$this->readyButton->setImage('http://127.0.0.1/elements/red-button.png', true);
		$this->readyButton->setImageFocus('http://127.0.0.1/elements/red-button-hover.png', true);
		$frame->addComponent($this->readyButton);
		
		$ui = new Elements\Label();
		$ui->setAlign('center', 'center');
		$ui->setPosY(-5);
		$ui->setStyle(Elements\Label::TextButtonMedium);
		$ui->setText('Ready');
		$frame->addComponent($ui);
		
		$ui = new Elements\Button();
		$ui->setHalign('center');
		$ui->setText('Rules');
		$ui->setManialink('');
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

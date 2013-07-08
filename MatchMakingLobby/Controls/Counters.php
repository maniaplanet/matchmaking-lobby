<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Controls;

use ManiaLib\Gui\Elements;
use ManiaLive\Gui\Controls\Frame;

class Counters extends \ManiaLive\Gui\Control
{

	static protected $waitingCount;
	static protected $playingCount;
	static protected $avgWaitingTime;
	static protected $nextMatchmaker;


	/**
	 * @var Frame
	 */
	protected $waitingFrame;
	
	/**
	 *
	 * @var Frame
	 */
	protected $playingFrame;
	
	/**
	 *
	 * @var Frame
	 */
	protected $waitingTimeFrame;
	
	/**
	 * @var Elements\Label
	 */
	protected $readyCountLabel;
	
	/**
	 * @var Elements\Label
	 */
	protected $playingCountLabel;
	
	/**
	 * @var Elements\Label
	 */
	protected $waitingTimeLabel;
	
	/**
	 * @var Elements\Label
	 */
	protected $nextMatchmakerLabel;

	/**
	 * @var int
	 */
	protected $heightMargin = 25;

	static function setPlayingCount($count)
	{
		static::$playingCount = $count;
	}

	static function setWaitingCount($count)
	{
		static::$waitingCount = $count;
	}

	static function setAverageWaitingTime($time)
	{
		static::$avgWaitingTime = $time;
	}
	
	static function setNextMatchmakerTime($time)
	{
		static::$nextMatchmaker = $time;
	}
	
	function getNextMatchmakerTimeDictionaryElement()
	{
		return array('textId' => 'nextMatch', 'params' => array(static::$nextMatchmaker));
	}
	
	function getNextMatchmakerTimeTextid()
	{
		return $this->nextMatchmakerLabel->getTextid();
	}
	
	function __construct()
	{
		$this->setSize(60, 40);
		$this->waitingFrame = new Frame(-5, 0);
		$this->addComponent($this->waitingFrame);
		$this->playingFrame = new Frame(5, 0);
		$this->addComponent($this->playingFrame);
		$this->waitingTimeFrame = new Frame(0, - $this->heightMargin);
		$this->addComponent($this->waitingTimeFrame);
		
		$ui = new Elements\Bgs1InRace(25, 12);
		$ui->setAlign('right');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/blue-quad-small.png',true);
		$this->waitingFrame->addComponent($ui);
		
		$ui = clone $ui;
		$ui->setHalign('left');
		$this->playingFrame->addComponent($ui);
		
		$ui = new Elements\Label(25);
		$ui->setAlign('center');
		$ui->setPosition(12.5, -13);
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setTextid('playing');
		$ui->setTextSize(2);
		$this->playingFrame->addComponent($ui);
		
		$ui = clone $ui;
		$ui->setTextid('ready');
		$ui->setPosition(-12.5);
		$this->waitingFrame->addComponent($ui);
		
		$this->playingCountLabel = new Elements\Label(25, 15);
		$this->playingCountLabel->setAlign('center', 'center2');
		$this->playingCountLabel->setPosition(12.5, -6);
		$this->playingCountLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->playingCountLabel->setTextSize(7);
		$this->playingCountLabel->setTextEmboss();
		$this->playingFrame->addComponent($this->playingCountLabel);
		
		$this->readyCountLabel = clone $this->playingCountLabel;
		$this->readyCountLabel->setPosX(-12.5);
		$this->waitingFrame->addComponent($this->readyCountLabel);
		
		$ui = new Elements\Bgs1InRace(40, 12);
		$ui->setAlign('center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/blue-quad-wide.png',true);
		$this->waitingTimeFrame->addComponent($ui);
		
		$ui = new Elements\Label(35,15);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, -14);
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setTextSize(2);
		$ui->setTextid('avgWaiting');
		$this->waitingTimeFrame->addComponent($ui);
		
		$this->waitingTimeLabel = new Elements\Label(35, 15);
		$this->waitingTimeLabel->setAlign('center', 'center2');
		$this->waitingTimeLabel->setPosition(0, -6);
		$this->waitingTimeLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->waitingTimeLabel->setTextSize(7);
		$this->waitingTimeLabel->setTextEmboss();
		$this->waitingTimeFrame->addComponent($this->waitingTimeLabel);
		
		$this->nextMatchmakerLabel = new Elements\Label(30);
		$this->nextMatchmakerLabel->setAlign('center', 'center2');
		$this->nextMatchmakerLabel->setStyle(Elements\Label::TextButtonSmall);
		$this->nextMatchmakerLabel->setPosition(0, -18);
		$this->nextMatchmakerLabel->setTextSize(0.7);
		$this->nextMatchmakerLabel->setTextColor('999');
		$this->nextMatchmakerLabel->setTextid('nextMatch');
		$this->waitingTimeFrame->addComponent($this->nextMatchmakerLabel);
	}
	
	function setHeightMargin($margin)
	{
		$this->heightMargin = $margin;
	}
	
	function onDraw()
	{
		if(static::$avgWaitingTime < 0)
		{
			$avgWaitingTime = '-';
		}
		else
		{
			$min = ceil(static::$avgWaitingTime / 60);
			$avgWaitingTime = sprintf('%d min',$min);
		}
		
		$this->waitingTimeFrame->setPosY(- $this->heightMargin);
		$this->playingCountLabel->setText(static::$playingCount);
		$this->readyCountLabel->setText(static::$waitingCount);
		$this->waitingTimeLabel->setText($avgWaitingTime);
//		$this->nextMatchmakerLabel->setText(sprintf('Next match in: %d sec', static::$nextMatchmaker));
	}

}

?>

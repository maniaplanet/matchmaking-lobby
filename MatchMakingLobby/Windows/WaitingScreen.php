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
	
	static public $scriptName;

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
	
	protected $playerList = array();
	
	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $palyerListFrame;
	
	protected $emptySlot;
	
	protected $textId;
		
	function onConstruct()
	{
		$ui = new Elements\Quad(320, 142);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/background.png',true);
		$this->addComponent($ui);
		
		$ui = new Elements\Quad(100, 15);
		$ui->setAlign('center', 'center');
		$ui->setPosY(62.5);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad-wide.png',true);
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
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad.png',true);
		$this->addComponent($ui);
		
		
		// TODO Add to Translation files
		$frame = new Frame();
		$frame->setPosition(95, 50);
		$this->addComponent($frame);
		
		$ui = new Elements\Bgs1InRace(40, 8);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad.png',true);
		$frame->addComponent($ui);
		
		$ui = new Elements\Label(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setTextEmboss();
		$ui->setText('Allies');
		$frame->addComponent($ui);
		
		$this->emptySlot = new \ManiaLive\Gui\Controls\Frame();
		$this->emptySlot->setSize(70, 20);
		
		$ui = new Elements\Quad(70, 20);
		$ui->setAlign('center', 'top');
		$ui->setBgcolor('0009');
		$this->emptySlot->addComponent($ui);
		
		$ui = new Elements\Label(70);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, -10);
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setText('Set another ally');
		$this->emptySlot->addComponent($ui);
		
		$this->playerListFrame = new \ManiaLive\Gui\Controls\Frame(0,-5, new \ManiaLib\Gui\Layouts\Column());
		$this->playerListFrame->getLayout()->setMarginHeight(3);
		$frame->addComponent($this->playerListFrame);

		// TODO Add to Translation files
		$ui = new Elements\Label(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setPosition(-95, 50);
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setTextEmboss();
		$ui->setText('Players');
		$this->addComponent($ui);
		
		$frame = new Frame();
		$frame->setPosition(0, 10);
		$this->addComponent($frame);

		$ui = new Elements\Bgs1InRace(25, 12);
		$ui->setAlign('right', 'center');
		$ui->setPosition(-5, 0);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/blue-quad-small.png',true);
		$frame->addComponent($ui);
		
		$uiLabel = new Elements\Label(25);
		$uiLabel->setAlign('center');
		$uiLabel->setPosition(-17.5, -7);
		$uiLabel->setStyle(Elements\Label::TextButtonSmall);
		$uiLabel->setTextid('playing');
		$uiLabel->setTextSize(2);
		$frame->addComponent($uiLabel);
		
		$this->playingCountLabel = new Elements\Label(25, 15);
		$this->playingCountLabel->setAlign('center', 'center2');
		$this->playingCountLabel->setPosition(-17.5, 0);
		$this->playingCountLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->playingCountLabel->setText('16');
		$this->playingCountLabel->setTextSize(7);
		$frame->addComponent($this->playingCountLabel);
		
		$ui = clone $ui;
		$ui->setHalign('left');
		$ui->setPosX(5);
		$frame->addComponent($ui);
		
		$uiLabel = clone $uiLabel;
		$uiLabel->setTextid('ready');
		$uiLabel->setPosition(17.5, -7);
		$frame->addComponent($uiLabel);
		
		$this->waitingCountLabel = clone $this->playingCountLabel;
		$this->waitingCountLabel->setPosX(17.5);
		$this->waitingCountLabel->setText(12);
		$frame->addComponent($this->waitingCountLabel);
		
		$ui = new Elements\Bgs1InRace(40, 12);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, -25);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/blue-quad-wide.png',true);
		$frame->addComponent($ui);
		
		$ui = new Elements\Label(35,15);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, -33.5);
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setTextSize(2);
		$ui->setText('AVG Waiting Time');
		$frame->addComponent($ui);
		
		$this->avgWaitTimeLabel = new Elements\Label(35, 15);
		$this->avgWaitTimeLabel->setAlign('center', 'center2');
		$this->avgWaitTimeLabel->setPosition(0, -25);
		$this->avgWaitTimeLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->avgWaitTimeLabel->setText('00:00');
		$this->avgWaitTimeLabel->setTextSize(7);
		$frame->addComponent($this->avgWaitTimeLabel);
		
		$this->buttonFrame = new Frame();
		$this->buttonFrame->setLayout(new \ManiaLib\Gui\Layouts\Column(0, 0, \ManiaLib\Gui\Layouts\Column::DIRECTION_UP));
		$this->buttonFrame->getLayout()->setMarginHeight(5);
		$this->buttonFrame->setPosY(-65);
		$this->addComponent($this->buttonFrame);

		$ui = new Elements\Label(40,5);
		$ui->setHalign('center', 'center2');
		$ui->setStyle(Elements\Label::TextButtonBig);
		$ui->setText('Back');
		$ui->setTextColor('fff');
		$ui->setAction('0');
		$this->buttonFrame->addComponent($ui);
		
		$frame = new Frame();
		$frame->setSize(60,3);
		$this->buttonFrame->addComponent($frame);
		
		$this->readyButton = new Elements\Quad(60,8);
		$this->readyButton->setAlign('center', 'center');
		$this->readyButton->setImage('http://static.maniaplanet.com/manialinks/lobbies/red-button.png', true);
		$this->readyButton->setImageFocus('http://static.maniaplanet.com/manialinks/lobbies/red-button-hover.png', true);
		$frame->addComponent($this->readyButton);
		
		$ui = new Elements\Label();
		$ui->setAlign('center', 'center2');
		$ui->setStyle(Elements\Label::TextButtonBig);
		$ui->setText('Ready');
		$ui->setTextSize(3);
		$frame->addComponent($ui);
		
		$ui = new Elements\Label(40,6);
		$ui->setHalign('center', 'center2');
		$ui->setStyle(Elements\Label::TextButtonBig);
		$ui->setText('Rules');
		$ui->setTextColor('fff');
		$ui->setManialink('');
		$this->buttonFrame->addComponent($ui);
	}
	
	function addAlly(\DedicatedApi\Structures\Player $player)
	{
		$path = explode('|', $player->path);
		$zone = $path[1];
		$this->playerList[$player->login] = new PlayerDetailed();
		$this->playerList[$player->login]->nickname = $player->nickName ? : $player->login;
		$this->playerList[$player->login]->zone = $zone;
		$this->playerList[$player->login]->avatarUrl = 'file://Avatars/'.$player->login.'/Default';
		$this->playerList[$player->login]->rank = $player->ladderStats['PlayerRankings'][0]['Ranking'];
		$this->playerList[$player->login]->setHalign('center');
	}
	
	function removeAlly($login)
	{
		if(array_key_exists($login, self::$playerList))
		{
			unset($this->playerList[$login]);
		}
	}
	
	function clearAlliesList()
	{
		$this->playerList = array();
	}
	
	function setTextId($textId = null)
	{
		$this->textId = $textId ? : array('textId' => 'helpText', 'params' => array('Elite'));
	}

	function onDraw()
	{
		if(self::$avgWaitTime < 0)
		{
			$avgWaitingTime = '-';
		}
		else
		{
			$min = self::$avgWaitTime / 60;
			$secs = ceil(($min - (int) $min) * 4) *15;
			$avgWaitingTime = sprintf('%1$02d:%2$02d',$min, $secs);
		}
		
		$this->playerListFrame->clearComponents();
		$playerKeys = array_keys($this->playerList);
		for($i = 0; $i < 2; $i++)
		{
			if(array_key_exists($i, $playerKeys))
			{
				$this->playerListFrame->addComponent($this->playerList[$playerKeys[$i]]);
			}
			else
			{
				$this->playerListFrame->addComponent(clone $this->emptySlot);
			}
		}
		
		$this->serverNameLabel->setText(self::$serverName);
		$this->playingCountLabel->setText(self::$playingCount);
		$this->waitingCountLabel->setText(self::$waitingCount);
		$this->avgWaitTimeLabel->setText($avgWaitingTime);
		$this->readyButton->setAction(self::$readyAction);
		$this->posZ = 3.9;
		$textId = $this->textId ? : array('textId' => 'helpText', 'params' => array('', self::$scriptName));
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink(array('playing' => 'playing', 'ready'=>'ready', 'text' => $textId)));
	}

}

?>

<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\MatchMakingLobby\Controls\PlayerDetailed;
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

class WaitingScreen extends \ManiaLive\Gui\Window
{

	/**
	 * @var int
	 */
	static protected $playingCount = 0;

	/**
	 * @var int
	 */
	static protected $waitingCount = 0;

	/**
	 * @var double
	 */
	static protected $avgWaitTime = -1;

	/**
	 * @var string
	 */
	static protected $serverName = '';

	/**
	 * @var string
	 */
	static protected $readyAction;

	/**
	 * @var string
	 */
	static protected $scriptName;

	/**
	 * @var int
	 */
	static protected $partySize;

	/**
	 * @var bool
	 */
	protected $disableReadyButton = false;

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
	
	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $readyButtonFrame;


	protected $playerList = array();
	
	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $palyerListFrame;
	
	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $emptySlot;
	
	/**
	 * @var string
	 */
	protected $textId;
	
	static function setReadyAction($action)
	{
		static::$readyAction = $action;
	}
	
	static function setServerName($name)
	{
		static::$serverName = $name;
	}
	
	static function setScriptName($script)
	{
		static::$scriptName = $script;
	}
	
	static function setPartySize($size)
	{
		static::$partySize = $size;
	}


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
		static::$avgWaitTime = $time;
	}
		
	function onConstruct()
	{
		$ui = new Elements\Quad(320, 125);
		$ui->setAlign('center', 'center');
		$ui->setBgcolor('888F');
		$this->addComponent($ui);
		
		$ui = new Elements\Quad(320, 142);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/background.png',true);
		$this->addComponent($ui);
		
		$ui = new Elements\Quad(100, 15);
		$ui->setAlign('center', 'center');
		$ui->setPosY(62.5);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad-wide.png',true);
		$this->addComponent($ui);

		$this->serverNameLabel = new Elements\Label(90, 20);
		$this->serverNameLabel->setAlign('center', 'center');
		$this->serverNameLabel->setPosY(62.5);
		$this->serverNameLabel->setStyle(Elements\Label::TextTitle1);
		$this->serverNameLabel->setTextEmboss();
		$this->addComponent($this->serverNameLabel);
		
		$ui = new Elements\Bgs1(110, 40);
		$ui->setPosY(3);
		$ui->setAlign('center');
		$ui->setSubStyle(Elements\Bgs1::BgHealthBar);
//		$ui->setBgcolor('4449');
		$this->addComponent($ui);
		
		$ui = new Elements\Label(100);
		$ui->setAlign('center');
		$ui->setPosY(-5);
		$ui->setTextColor('fff');
		$ui->setTextSize(3);
		$ui->enableAutonewline();
		$ui->setTextid('text');
		$this->addComponent($ui);

		$ui = new Elements\Bgs1InRace(40, 8);
		$ui->setAlign('center', 'center');
		$ui->setPosition(-108, 50);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad.png',true);
		$this->addComponent($ui);
		
		
		// TODO Add to Translation files
		$frame = new Frame();
		$frame->setPosition(108, 50);
		$this->addComponent($frame);
		
		$ui = new Elements\Bgs1InRace(40, 8);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad.png',true);
		$frame->addComponent($ui);
		
		$ui = new Elements\Label(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setTextEmboss();
		$ui->setTextid('allies');
		$frame->addComponent($ui);
		
		$this->emptySlot = new \ManiaLive\Gui\Controls\Frame();
		$this->emptySlot->setSize(70, 20);
		
		$ui = new Elements\Bgs1(80, 20);
		$ui->setAlign('center', 'top');
		$ui->setSubStyle(Elements\Bgs1::BgListLine);
		$this->emptySlot->addComponent($ui);
		
		$ui = new Elements\Label(80);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, -10);
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setTextid('setAlly');
//		$this->emptySlot->addComponent($ui);
		
		$this->playerListFrame = new \ManiaLive\Gui\Controls\Frame(0, -7, new \ManiaLib\Gui\Layouts\Column());
		$this->playerListFrame->getLayout()->setMarginHeight(3);
		$frame->addComponent($this->playerListFrame);

		$ui = new Elements\Label(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setPosition(-108, 50);
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setTextEmboss();
		$ui->setTextid('players');
		$this->addComponent($ui);
		
		$frame = new Frame();
		$frame->setPosition(0, 45);
		$this->addComponent($frame);

		$ui = new Elements\Bgs1InRace(25, 12);
		$ui->setAlign('right', 'center');
		$ui->setPosition(-5, 0);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/blue-quad-small.png',true);
		$frame->addComponent($ui);
		
		$uiLabel = new Elements\Label(25);
		$uiLabel->setAlign('center');
		$uiLabel->setPosition(17.5, -7);
		$uiLabel->setStyle(Elements\Label::TextButtonSmall);
		$uiLabel->setTextid('playing');
		$uiLabel->setTextSize(2);
		$frame->addComponent($uiLabel);
		
		$ui = clone $ui;
		$ui->setHalign('left');
		$ui->setPosX(5);
		$frame->addComponent($ui);
		
		$uiLabel = clone $uiLabel;
		$uiLabel->setTextid('ready');
		$uiLabel->setPosition(-17.5, -7);
		$frame->addComponent($uiLabel);
		
		$this->playingCountLabel = new Elements\Label(25, 15);
		$this->playingCountLabel->setAlign('center', 'center2');
		$this->playingCountLabel->setPosition(17.5, 0);
		$this->playingCountLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->playingCountLabel->setText('16');
		$this->playingCountLabel->setTextSize(7);
		$this->playingCountLabel->setTextEmboss();
		$frame->addComponent($this->playingCountLabel);
		
		$this->waitingCountLabel = clone $this->playingCountLabel;
		$this->waitingCountLabel->setPosX(-17.5);
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
		$ui->setTextid('avgWaiting');
		$frame->addComponent($ui);
		
		$this->avgWaitTimeLabel = new Elements\Label(35, 15);
		$this->avgWaitTimeLabel->setAlign('center', 'center2');
		$this->avgWaitTimeLabel->setPosition(0, -25);
		$this->avgWaitTimeLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$this->avgWaitTimeLabel->setText('00:00');
		$this->avgWaitTimeLabel->setTextSize(7);
		$this->avgWaitTimeLabel->setTextEmboss();
		$frame->addComponent($this->avgWaitTimeLabel);
		
		$this->buttonFrame = new Frame();
		$this->buttonFrame->setLayout(new \ManiaLib\Gui\Layouts\Column(0, 0, \ManiaLib\Gui\Layouts\Column::DIRECTION_UP));
		$this->buttonFrame->getLayout()->setMarginHeight(5);
		$this->buttonFrame->setPosY(-65);
		$this->addComponent($this->buttonFrame);

		$ui = new Elements\Label(40,5);
		$ui->setHalign('center', 'center2');
		$ui->setStyle(Elements\Label::TextButtonBig);
		$ui->setTextid('back');
		$ui->setTextColor('fff');
		$ui->setAction('maniaplanet:quitserver');
		$this->buttonFrame->addComponent($ui);
		
		$this->readyButtonFrame = new Frame();
		$this->readyButtonFrame->setSize(60,3);
		$this->readyButtonFrame->setPosition(0, -30);
		$this->addComponent($this->readyButtonFrame);
		
		$this->readyButton = new Elements\Quad(60,8);
		$this->readyButton->setAlign('center', 'center');
		$this->readyButton->setImage('http://static.maniaplanet.com/manialinks/lobbies/red-button.png', true);
		$this->readyButton->setImageFocus('http://static.maniaplanet.com/manialinks/lobbies/red-button-hover.png', true);
		$this->readyButtonFrame->addComponent($this->readyButton);
		
		$ui = new Elements\Label();
		$ui->setAlign('center', 'center2');
		$ui->setStyle(Elements\Label::TextButtonBig);
		$ui->setTextid('readyButton');
		$ui->setTextSize(3);
		$this->readyButtonFrame->addComponent($ui);
		
		$ui = new Elements\Label(40,6);
		$ui->setHalign('center', 'center2');
		$ui->setStyle(Elements\Label::TextButtonBig);
		$ui->setTextid('rules');
		$ui->setTextColor('fff');
		$ui->setManialink('');
		$this->buttonFrame->addComponent($ui);
	}
	
	function createParty(\DedicatedApi\Structures\Player $player)
	{
		$this->addPlayerToParty($player);
		
		foreach($player->allies as $ally)
		{
			$this->addPlayerToParty(\ManiaLive\Data\Storage::getInstance()->getPlayerObject($ally));
		}
	}
	
	protected function addPlayerToParty(\DedicatedApi\Structures\Player $player)
	{
		$path = explode('|', $player->path);
		$zone = $path[1];
		$this->playerList[$player->login] = new PlayerDetailed();
		$this->playerList[$player->login]->nickname = $player->nickName ? : $player->login;
		$this->playerList[$player->login]->zone = $zone;
		$this->playerList[$player->login]->avatarUrl = 'file://Avatars/'.$player->login.'/Default';
		$this->playerList[$player->login]->rank = $player->ladderStats['PlayerRankings'][0]['Ranking'];
		$this->playerList[$player->login]->echelon = floor($player->ladderStats['PlayerRankings'][0]['Score'] / 10000);
		$this->playerList[$player->login]->countryFlagUrl = sprintf('file://ZoneFlags/Login/%s/country', $player->login);
		$this->playerList[$player->login]->setHalign('center');
	}
	
	function removeAlly($login)
	{
		if(array_key_exists($login, self::$playerList))
		{
			unset($this->playerList[$login]);
		}
	}
	
	function disableReadyButton($disable = true)
	{
		$this->disableReadyButton = $disable;
	}
	
	function clearParty()
	{
		$this->playerList = array();
	}
	
	function setTextId($textId = null)
	{
		$this->textId = $textId ? : array('textId' => 'waitingHelp', 'params' => array(static::$scriptName));
	}
	
	function onDraw()
	{
		if(static::$avgWaitTime < 0)
		{
			$avgWaitingTime = '-';
		}
		else
		{
			$min = ceil(static::$avgWaitTime / 60);
			$avgWaitingTime = sprintf('%d min',$min);
		}
		
		$this->playerListFrame->clearComponents();
		$playerKeys = array_keys($this->playerList);
		for($i = 0; $i < static::$partySize; $i++)
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
		
		$this->serverNameLabel->setText(static::$serverName);
		$this->playingCountLabel->setText(static::$playingCount);
		$this->waitingCountLabel->setText(static::$waitingCount);
		$this->avgWaitTimeLabel->setText($avgWaitingTime);
		if(!$this->disableReadyButton)
		{
			$this->readyButtonFrame->setVisibility(true);
			$this->readyButton->setAction(static::$readyAction);
		}
		else
		{
			$this->readyButtonFrame->setVisibility(false);
		}
		$this->posZ = 3.9;

		$textId = $this->textId ? : array('textId' => 'waitingHelp', 'params' => array(static::$scriptName));
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink(array(
				'playing' => 'playing',
				'ready' => 'ready',
				'readyButton' => 'readyButton',
				'text' => $textId,
				'players' => 'players',
				'allies' => 'party',
				'avgWaiting' => 'waitingScreenWaitingLabel',
				'rules' => 'rules',
				'back' => 'back',
				'setAlly' => 'setAlly'
		)));
	}

}

?>
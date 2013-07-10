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
	 * @var Elements\Quad
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
	 * @var \ManiaLivePlugins\MatchMakingLobby\Controls\Counters
	 */
	protected $counters;
	
	/**
	 * @var string
	 */
	protected $textId;
	
	protected $dico = array();


	static function setReadyAction($action)
	{
		static::$readyAction = $action;
	}
	
	static function setScriptName($script)
	{
		static::$scriptName = $script;
	}
	
	static function setPartySize($size)
	{
		static::$partySize = $size;
	}

	function onConstruct()
	{
		$this->dico = array(
			'playing' => 'playing',
				'ready' => 'ready',
				'readyButton' => 'readyButton',
				'players' => 'players',
				'allies' => 'party',
				'avgWaiting' => 'waitingScreenWaitingLabel',
				'rules' => 'rules',
				'back' => 'quit',
		);
		
		$ui = new Elements\Quad(320, 125);
		$ui->setAlign('center', 'center');
		$ui->setBgcolor('888A');
		$this->addComponent($ui);
		
		$ui = new Elements\Quad(320, 142);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/background.png',true);
		$this->addComponent($ui);
		
		$ui = new \ManiaLivePlugins\MatchMakingLobby\Controls\ServerName();
		$ui->setPosY(62.5);
		$ui->setAlign('center','center');
		$this->addComponent($ui);
		
		$ui = new Elements\Bgs1(110, 40);
		$ui->setPosY(3);
		$ui->setAlign('center');
		$ui->setSubStyle(Elements\Bgs1::BgHealthBar);
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
		
		$this->emptySlot = new \ManiaLivePlugins\MatchMakingLobby\Controls\EmptySlot();
		$this->emptySlot->setSize(80, 20);
		$this->emptySlot->setAlign('center');
		$this->dico[$this->emptySlot->getLabelTextid()] = 'picked';
		
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
		
		$this->counters = new \ManiaLivePlugins\MatchMakingLobby\Controls\Counters();
		$this->counters->setPosition(0, 50);
		$this->addComponent($this->counters);
		
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
		
		$ui = new Elements\Label(58);
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
			$allyObject = \ManiaLive\Data\Storage::getInstance()->getPlayerObject($ally);
			if($allyObject)
			{
				$this->addPlayerToParty($allyObject);
			}
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
		if($this->disableReadyButton)
		{
			$this->readyButtonFrame->setVisibility(false);
		}
		else
		{
			$this->readyButtonFrame->setVisibility(true);
			$this->readyButton->setAction(static::$readyAction);
		}
		
		$this->posZ = 3.9;

		$textId = $this->textId ? : array('textId' => 'waitingHelp', 'params' => array(static::$scriptName));
		$this->dico['text'] = $textId;
		$this->dico[$this->counters->getNextMatchmakerTimeTextid()] = $this->counters->getNextMatchmakerTimeDictionaryElement();
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink($this->dico));
	}

}

?>
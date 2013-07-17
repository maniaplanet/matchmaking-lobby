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

	const SIZE_X = 136;
	const SIZE_Y = 94;
	
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
	 * @var string
	 */
	static protected $logoLink;

	/**
	 * @var string
	 */
	static protected $logoURL;

	/**
	 * @var bool
	 */
	protected $disableReadyButton = false;

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
	 * @var Elements\Quad
	 */
	protected $logo;

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
	
	static function setLogo($URL, $link = '')
	{
		static::$logoURL = $URL;
		static::$logoLink = $link;
	}
	
	function onConstruct()
	{
		$this->setLayer(\ManiaLive\Gui\Window::LAYER_CUT_SCENE);
		
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
		
		$ui = new Elements\Quad(320, 20);
		$ui->setAlign('center', 'bottom');
		$ui->setBgcolor('000');
		$ui->setPosition(0,-90);
		$this->addComponent($ui);
		
		$ui = new Elements\Label(320, 20);
		
		$ui = new Elements\Quad(self::SIZE_X, self::SIZE_Y);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/2013-07-15/main-bg.png',true);
		$this->addComponent($ui);
		
		$ui = new Elements\Label(self::SIZE_X);
		$ui->setAlign('center', 'top');
		$ui->setPosition(0, 38);
		$ui->setTextColor('fff');
		$ui->setTextSize(3);
		$ui->enableAutonewline();
		$ui->setTextid('text');
		$ui->setOpacity(0.9);
		$this->addComponent($ui);
		
		$frame = new Frame();
		$frame->setScale(0.6);
		$frame->setPosition(0, 20);
		$this->addComponent($frame);
		
		$this->emptySlot = new \ManiaLivePlugins\MatchMakingLobby\Controls\EmptySlot();
		$this->emptySlot->setSize(80, 20);
		$this->emptySlot->setAlign('center');
		$this->dico[$this->emptySlot->getLabelTextid()] = 'picked';
		
		$this->playerListFrame = new \ManiaLive\Gui\Controls\Frame(0, 0, new \ManiaLib\Gui\Layouts\Column());
		$this->playerListFrame->getLayout()->setMarginHeight(3);
		$frame->addComponent($this->playerListFrame);

		$ui = new Elements\Label(40,5);
		$ui->setHalign('center', 'center2');
		$ui->setStyle(Elements\Label::TextButtonBig);
		$ui->setTextid('back');
		$ui->setTextColor('fff');
		$ui->setAction('maniaplanet:quitserver');
		
		//ready button start
		$this->readyButtonFrame = new Frame();
		$this->readyButtonFrame->setSize(48,10);
		$this->readyButtonFrame->setPosition(0, -34);
		$this->addComponent($this->readyButtonFrame);
	
		$this->readyButton = new Elements\Quad(48,10);
		$this->readyButton->setAlign('center', 'center');
		$this->readyButton->setImage('http://static.maniaplanet.com/manialinks/lobbies/2013-07-15/ready-button-GREEN.dds', true);
		$this->readyButton->setImageFocus('http://static.maniaplanet.com/manialinks/lobbies/2013-07-15/ready-button-GREEN-ON.dds', true);
		$this->readyButtonFrame->addComponent($this->readyButton);
		
		$ui = new Elements\Label(48);
		$ui->setAlign('center', 'center2');
		$ui->setStyle(Elements\Label::TextRaceMessageBig);
		$ui->setTextid('readyButton');
		$ui->setOpacity(0.7);
		$ui->setTextSize(3);
		$this->readyButtonFrame->addComponent($ui);
		//ready button  end
		
		$ui = new Elements\Label(40,6);
		$ui->setHalign('center', 'center2');
		$ui->setStyle(Elements\Label::TextButtonBig);
		$ui->setTextid('rules');
		$ui->setTextColor('fff');
		$ui->setManialink('');
		
		$this->logo = new Elements\Quad(80, 20);
		$this->logo->setAlign('center', 'top');
		$this->logo->setPosY(90);
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
		
		if(static::$logoURL)
		{
			$this->logo->setImage(static::$logoURL, true);
			$this->logo->setUrl(static::$logoLink);
			$this->addComponent($this->logo);
		}
		else
		{
			$this->removeComponent($this->logo);
		}

		$textId = $this->textId ? : array('textId' => 'waitingHelp', 'params' => array(static::$scriptName));
		$this->dico['text'] = $textId;
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink($this->dico));
	}

}

?>
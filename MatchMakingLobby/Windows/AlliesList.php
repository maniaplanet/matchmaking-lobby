<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLivePlugins\MatchMakingLobby\Controls\PlayerDetailed;
use ManiaLib\Gui\Elements;

class AlliesList extends \ManiaLive\Gui\Window
{
	protected $playerList = array();
	
	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $playerListFrame;
	
	protected $emptySlot;


	protected function onConstruct()
	{
		$this->setSize(70, 100);
		
		$ui = new Elements\Bgs1InRace(40, 8);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad.png',true);
		$this->addComponent($ui);
		
		// TODO Add to Translation files
		$ui = new Elements\Label(40, 10);
		$ui->setAlign('center', 'center');
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setTextEmboss();
		$ui->setText('Allies');
		$this->addComponent($ui);
		
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
		$this->addComponent($this->playerListFrame);
	}
	
	function addPlayer(\DedicatedApi\Structures\Player $player)
	{
		$path = explode('|', $player->path);
		$zone = $path[1];
		$this->playerList[$player->login] = new PlayerDetailed();
		$this->playerList[$player->login]->nickname = $player->nickName ? : $player->login;
		$this->playerList[$player->login]->zone = $zone;
		$this->playerList[$player->login]->avatarUrl = 'file://Avatars/'.$player->login.'/Default';
		$this->playerList[$player->login]->rank = $player->ladderStats['PlayerRankings'][0]['Ranking'];
		$this->playerList[$player->login]->echelon = floor($player->ladderStats['PlayerRankings'][0]['Score'] / 10000);
		$this->playerList[$player->login]->setHalign('center');
	}
	
	function removePlayer($login)
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
	
	function onDraw()
	{
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
		$this->posZ = 5;
	}
}

?>

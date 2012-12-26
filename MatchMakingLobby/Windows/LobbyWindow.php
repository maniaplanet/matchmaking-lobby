<?php

/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9108 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-13 17:15:36 +0100 (jeu., 13 déc. 2012) $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;

class LobbyWindow extends \ManiaLive\Gui\Window
{
	/**
	 * @var Elements\Label
	 */
	protected $serverName;
	
	/**
	 * @var Elements\Label
	 */
	protected $readyPlayers;
	
	/**
	 * @var Elements\Label
	 */
	protected $totalPlayers;

	protected function onConstruct()
	{
		$this->setSize(50, 25);
		
		$ui = new Elements\Label(40);
		$ui->setStyle(Elements\Label::TextTitle3);
		$ui->setText('$s$999Lobby info');
		$ui->setAlign('center','center2');
		$ui->setPosition(20);
		$this->addComponent($ui);
		
		$ui = new Elements\Bgs1InRace(50, 20);
		$ui->setSubStyle(Elements\Bgs1InRace::BgListLine);
		$ui->setPosY(-2);
		$this->addComponent($ui);
		
		$this->serverName = new Elements\Label(38);
		$this->serverName->setAlign('center', 'center2');
		$this->serverName->setPosition(20, -5);
		$this->serverName->setTextColor('fff');
		$this->serverName->setText('NadeoLive Test');
		$this->addComponent($this->serverName);
		
		$ui = new Elements\Label(30);
		$ui->setText('Players');
		$ui->setStyle(null);
		$ui->setAlign('center', 'center2');
		$ui->setPosition(20, -9);
		$ui->setScale(0.6);
		$this->addComponent($ui);
		
		$ui = new Elements\Label(30);
		$ui->setText('Ready');
		$ui->setStyle(null);
		$ui->setAlign('center', 'center2');
		$ui->setPosition(10, -12);
		$ui->setScale(0.6);
		$this->addComponent($ui);
		
		
		$this->readyPlayers = new Elements\Label(17);
		$this->readyPlayers->setAlign('center', 'center2');
		$this->readyPlayers->setPosition(10, -18);
		$this->readyPlayers->setStyle(Elements\Label::TextRaceChrono);
		$this->readyPlayers->setText(6);
		$this->readyPlayers->setScale(0.75);
		$this->addComponent($this->readyPlayers);
		
		$ui = new Elements\Label(30);
		$ui->setText('Total');
		$ui->setStyle(null);
		$ui->setAlign('center', 'center2');
		$ui->setPosition(30, -12);
		$ui->setScale(0.6);
		$this->addComponent($ui);
		
		$this->totalPlayers = new Elements\Label(17);
		$this->totalPlayers->setAlign('center', 'center2');
		$this->totalPlayers->setPosition(30, -18);
		$this->totalPlayers->setStyle(Elements\Label::TextRaceChrono);
		$this->totalPlayers->setText(6);
		$this->totalPlayers->setScale(0.75);
		$this->addComponent($this->totalPlayers);
	}
	
	function set($serverName, $readyPlayersCount, $totalPlayers)
	{
		$this->serverName->setText($serverName);
		$this->readyPlayers->setText($readyPlayersCount);
		$this->totalPlayers->setText($totalPlayers);
	}
}

?>
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
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

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
	protected $playingPlayers;

	/**
	 * @var Elements\Label
	 */
	protected $averageTime;

	/** @var array */
	protected $dico;

	protected function onConstruct()
	{
		$this->setSize(50, 26);

		$this->dico = array(
			'playing' => 'playing',
			'ready' => 'ready',
			'avgWaiting' => 'waitingScreenWaitingLabel'
		);

		$ui = new Elements\Bgs1InRace(50, 26);
		$ui->setSubStyle(Elements\Bgs1InRace::BgListLine);
		$ui->setPosY(-2);
		$this->addComponent($ui);
		
		$ui = new Elements\Quad(45, 7);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad-wide.png',true);
		$this->addComponent($ui);

		$this->serverName = new Elements\Label(38);
		$this->serverName->setAlign('center', 'center2');
		$this->serverName->setPosition(22.5, -3.5);
		$this->serverName->setTextColor('fff');
		$this->serverName->setTextSize(1);
		$this->addComponent($this->serverName);

		$ui = new Elements\Bgs1InRace(20, 6);
		$ui->setAlign('center', 'center');
		$ui->setPosition(22.5, -21);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/blue-quad-wide.png',true);
		$this->addComponent($ui);
		
		$this->averageTime = new Elements\Label(65);
		$this->averageTime->setStyle(null);
		$this->averageTime->setAlign('center', 'center2');
		$this->averageTime->setPosition(22.5, -21);
		$this->averageTime->setStyle(Elements\Label::TextRaceMessageBig);
		$this->averageTime->setTextSize(3);
		$this->addComponent($this->averageTime);
		
		$ui = new Elements\Label(30);
		$ui->setTextId('avgWaiting');
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setAlign('center', 'center2');
		$ui->setPosition(22.5, -26);
		$ui->setTextSize(0.5);
		$this->addComponent($ui);

		$ui = new Elements\Label(30);
		$ui->setTextid('ready');
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setAlign('center', 'center2');
		$ui->setPosition(11, -16);
		$ui->setTextSize(0.5);
		$this->addComponent($ui);

		$ui = new Elements\Bgs1InRace(15, 7.2);
		$ui->setAlign('center', 'center');
		$ui->setPosition(11, -11);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/blue-quad-small.png',true);
		$this->addComponent($ui);

		$this->readyPlayers = new Elements\Label(10);
		$this->readyPlayers->setAlign('center', 'center2');
		$this->readyPlayers->setPosition(11, -11);
		$this->readyPlayers->setStyle(Elements\Label::TextRaceMessageBig);
		$this->readyPlayers->setTextSize(4);
		$this->addComponent($this->readyPlayers);

		$ui = new Elements\Label(30);
		$ui->setTextId('playing');
		$ui->setStyle(Elements\Label::TextButtonSmall);
		$ui->setAlign('center', 'center2');
		$ui->setPosition(33, -16);
		$ui->setTextSize(0.5);
		$this->addComponent($ui);

		$ui = new Elements\Bgs1InRace(15, 7.2);
		$ui->setAlign('center', 'center');
		$ui->setPosition(33, -11);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/blue-quad-small.png',true);
		$this->addComponent($ui);

		$this->playingPlayers = new Elements\Label(10);
		$this->playingPlayers->setAlign('center', 'center2');
		$this->playingPlayers->setPosition(33, -11);
		$this->playingPlayers->setStyle(Elements\Label::TextRaceMessageBig);
		$this->playingPlayers->setTextSize(4);
		$this->addComponent($this->playingPlayers);
		
		
	}

	function set($serverName, $readyPlayersCount, $playingPlayersCount, $averageTime)
	{
		$this->serverName->setText($serverName);
		$this->readyPlayers->setText($readyPlayersCount);
		$this->playingPlayers->setText($playingPlayersCount);
		if($averageTime == -1)
		{
			$average = '-';
		}
		else
		{
			$average = sprintf('%d min', ceil($averageTime / 60));
		}
		$this->averageTime->setText($average);
	}

	function onDraw()
	{
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink($this->dico));
	}

}

?>
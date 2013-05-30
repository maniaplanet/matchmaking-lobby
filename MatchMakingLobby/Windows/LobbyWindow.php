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
	protected $playingPlayers;

	/**
	 * @var Elements\Label
	 */
	protected $averageTime;

	/** @var array */
	protected $dico;

	protected function onConstruct()
	{
		$this->setSize(50, 25);

		$this->dico = array(
			'fr' => array(
				'waitingTime' => 'Temps d\'attente estimé : ',
				'playing' => 'En jeu',
				'ready' => 'Prêt',
			),
			'en' => array(
				'waitingTime' => 'Estimated waiting time: ',
				'playing' => 'Playing',
				'ready' => 'Ready',
			)
		);

		$ui = new Elements\Bgs1InRace(50, 20);
		$ui->setSubStyle(Elements\Bgs1InRace::BgListLine);
		$ui->setPosY(-2);
		$this->addComponent($ui);

		$this->serverName = new Elements\Label(38);
		$this->serverName->setAlign('center', 'center2');
		$this->serverName->setPosition(22.5, -5);
		$this->serverName->setTextColor('fff');
		$this->addComponent($this->serverName);

		$this->averageTime = new Elements\Label(65);
		$this->averageTime->setTextId('waitingTime');
		$this->averageTime->setStyle(null);
		$this->averageTime->setAlign('left', 'center2');
		$this->averageTime->setPosition(3, -9);
		$this->averageTime->setScale(0.6);
		$this->addComponent($this->averageTime);

		$ui = new Elements\Label(30);
		$ui->setTextid('ready');
		$ui->setStyle(null);
		$ui->setAlign('center', 'center2');
		$ui->setPosition(11, -12);
		$ui->setScale(0.6);
		$this->addComponent($ui);


		$this->readyPlayers = new Elements\Label(17);
		$this->readyPlayers->setAlign('center', 'center2');
		$this->readyPlayers->setPosition(11, -18);
		$this->readyPlayers->setStyle(Elements\Label::TextRaceChrono);
		$this->readyPlayers->setText(6);
		$this->readyPlayers->setScale(0.75);
		$this->addComponent($this->readyPlayers);

		$ui = new Elements\Label(30);
		$ui->setTextId('playing');
		$ui->setStyle(null);
		$ui->setAlign('center', 'center2');
		$ui->setPosition(33, -12);
		$ui->setScale(0.6);
		$this->addComponent($ui);


		$this->playingPlayers = new Elements\Label(17);
		$this->playingPlayers->setAlign('center', 'center2');
		$this->playingPlayers->setPosition(33, -18);
		$this->playingPlayers->setStyle(Elements\Label::TextRaceChrono);
		$this->playingPlayers->setText(6);
		$this->playingPlayers->setScale(0.75);
		$this->addComponent($this->playingPlayers);

	}

	function set($serverName, $readyPlayersCount, $playingPlayersCount, $averageTime)
	{
		$this->serverName->setText($serverName);
		$this->readyPlayers->setText($readyPlayersCount);
		$this->playingPlayers->setText($playingPlayersCount);
		if($averageTime == -1)
		{
			$this->dico['fr']['waitingTime'] = 'Temps moyen d\'attente : -';
			$this->dico['en']['waitingTime'] = 'Average waiting time: -';
		}
		else
		{
			$average = ceil($averageTime / 60);
			$this->dico['fr']['waitingTime'] = sprintf('Temps d\'attente moyen : %d min', $average);
			$this->dico['en']['waitingTime'] = sprintf('Average waiting time: %d min', $average);
		}
	}

	function onDraw()
	{
		\ManiaLive\Gui\Manialinks::appendXML(\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::build($this->dico));
	}

}

?>
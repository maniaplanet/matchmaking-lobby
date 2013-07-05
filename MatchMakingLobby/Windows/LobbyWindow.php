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
	 * @var \ManiaLivePlugins\MatchMakingLobby\Controls\Counters
	 */
	protected $counters;


	/** @var array */
	protected $dico;

	protected function onConstruct()
	{
		$this->setSize(50, 28);

		$this->dico = array(
			'playing' => 'playing',
			'ready' => 'ready',
			'avgWaiting' => 'waitingScreenWaitingLabel'
		);

		$ui = new Elements\Bgs1InRace(49, 28);
//		$ui->setSubStyle(Elements\Bgs1InRace::BgListLine);
		$ui->setBgcolor('111A');
		$ui->setPosition(0.2, -2);
		$this->addComponent($ui);
		
		$ui = new \ManiaLivePlugins\MatchMakingLobby\Controls\ServerName();
		$ui->setScale(0.45);
		$this->addComponent($ui);

		$this->counters = new \ManiaLivePlugins\MatchMakingLobby\Controls\Counters();
		$this->counters->setPosition(23, -7);
		$this->counters->setHeightMargin(18);
		$this->counters->setScale(0.6);
		$this->addComponent($this->counters);
	}

	function set($serverName, $readyPlayersCount, $playingPlayersCount, $averageTime)
	{
	}

	function onDraw()
	{
		$this->dico[$this->counters->getNextMatchmakerTimeTextid()] = $this->counters->getNextMatchmakerTimeDictionaryElement();
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink($this->dico));
	}

}

?>
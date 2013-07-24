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
	const SIZE_X = 91;
	
	const SIZE_Y = 27;
	/** @var array */
	protected $dico;
	
	/**
	 * @var Elements\Quad 
	 */
	protected $bg;
	
	/**
	 * @var Elements\Label 
	 */
	protected $serverNameLabel;
	
	/**
	 * @var Elements\Label 
	 */
	protected $avgWaitingTimeLabel;
	
	/**
	 * @var Elements\Label 
	 */
	protected $playerCountLabel;
	
	
	/**
	 * @var Elements\Label 
	 */
	protected $avgWaitingTimeHelperLabel;
	
	/**
	 * @var Elements\Label 
	 */
	protected $playerCountHelperLabel;
	
	static protected $playingPlayerCount;
	static protected $readyPlayerCount;
	static protected $avgWaitingTime;
	static protected $serverName;
	
	protected function onConstruct()
	{
		$this->setLayer(\ManiaLive\Gui\Window::LAYER_CUT_SCENE);
		
		$this->setSize(self::SIZE_X, self::SIZE_Y);
		
		$this->setRelativeAlign('center', 'top');
		
		$this->setPosition(0, 84);

		$this->bg = new Elements\Quad(self::SIZE_X, self::SIZE_Y);
		$this->bg->setImage('http://static.maniaplanet.com/manialinks/lobbies/2013-07-15/header.png');
		$this->bg->setAlign('center');
		$this->addComponent($this->bg);
		
		$this->serverNameLabel = new Elements\Label(self::SIZE_X-7);
		$this->serverNameLabel->setStyle(Elements\Label::TextRaceMessage);
		$this->serverNameLabel->setAlign('center', 'top');
		$this->serverNameLabel->setPosition(0, -4);
		$this->serverNameLabel->setTextSize(3);
		$this->addComponent($this->serverNameLabel);
		
		$this->avgWaitingTimeLabel = new Elements\Label(self::SIZE_X/3);
		$this->avgWaitingTimeLabel->setAlign('right', 'center');
		$this->avgWaitingTimeLabel->setStyle(Elements\Label::TextRaceMessage);
		$this->avgWaitingTimeLabel->setPosition(self::SIZE_X/2-5,-16);
		$this->avgWaitingTimeLabel->setTextId('avgWaiting');
		$this->avgWaitingTimeLabel->setTextSize(2);
		$this->avgWaitingTimeLabel->setOpacity(0.75);
		$this->addComponent($this->avgWaitingTimeLabel);
		
		$this->avgWaitingTimeHelperLabel = new Elements\Label(self::SIZE_X/2);
		$this->avgWaitingTimeHelperLabel->setAlign('right', 'top');
		$this->avgWaitingTimeHelperLabel->setStyle(Elements\Label::TextRaceMessage);
		$this->avgWaitingTimeHelperLabel->setPosition($this->avgWaitingTimeLabel->getPosX(),-18);
		$this->avgWaitingTimeHelperLabel->setTextId('avgWaitingHelper');
		$this->avgWaitingTimeHelperLabel->setScale(0.5);
		$this->avgWaitingTimeHelperLabel->setOpacity(0.5);
		$this->addComponent($this->avgWaitingTimeHelperLabel);
		
		$this->playerCountLabel = new Elements\Label(self::SIZE_X/3);
		$this->playerCountLabel->setAlign('left', 'center');
		$this->playerCountLabel->setStyle(Elements\Label::TextRaceMessage);
		$this->playerCountLabel->setPosition(-self::SIZE_X/2+5, -16);
		$this->playerCountLabel->setOpacity(0.75);
		$this->playerCountLabel->setTextSize(2);
		$this->playerCountLabel->setTextid('nPlayers');
		$this->addComponent($this->playerCountLabel);
		
		$this->playerCountHelperLabel = new Elements\Label(self::SIZE_X/2);
		$this->playerCountHelperLabel->setAlign('left', 'top');
		$this->playerCountHelperLabel->setStyle(Elements\Label::TextRaceMessage);
		$this->playerCountHelperLabel->setPosition($this->playerCountLabel->getPosX(),-18);
		$this->playerCountHelperLabel->setTextId('nPlayersHelper');
		$this->playerCountHelperLabel->setScale(0.5);
		$this->playerCountHelperLabel->setOpacity(0.5);
		$this->addComponent($this->playerCountHelperLabel);
	}
	
	static function setPlayingPlayerCount($count)
	{
		static::$playingPlayerCount = $count;
	}

	static function setAverageWaitingTime($time)
	{
		static::$avgWaitingTime = $time;
	}
	
	static function setServerName($serverName)
	{
		static::$serverName = $serverName;
	}
	
	static function setReadyPlayerCount($count)
	{
		static::$readyPlayerCount = $count;
	}

	function onDraw()
	{
		$this->dico = array(
			'playing' => 'playing',
			'ready' => 'ready',
			'avgWaitingHelper' => 'avgWaitingHelper',
			'avgWaiting' => array('textId' => 'avgWaitingTime', 'params' => array(static::$avgWaitingTime)),
			'nPlayers' => array('textId' => 'nPlayersPlaying', 'params' => array(static::$playingPlayerCount)),
			'nPlayersHelper' => array('textId' => 'nPlayersReady', 'params' => array(static::$readyPlayerCount))
		);
				
		$this->serverNameLabel->setText(static::$serverName);
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink($this->dico));
	}

}

?>
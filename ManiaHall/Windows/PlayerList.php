<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9091 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-12 16:37:36 +0100 (mer., 12 déc. 2012) $:
 */

namespace ManiaLivePlugins\ManiaHall\Windows;

class PlayerList extends \ManiaLive\Gui\Window
{
	/**
	 * @var \ManiaLive\Gui\Controls\Frame
	 */
	protected $frame;
	
	protected $playerList = array();
	
	protected function onConstruct()
	{
		$this->setSize(50, 100);
		
		$this->frame = new \ManiaLive\Gui\Controls\Frame(0, 0, new \ManiaLib\Gui\Layouts\Column());
		$this->playerList = array();
		$this->addComponent($this->frame);
	}
	
	function addPlayer($login, $ready = false)
	{
		$storage = \ManiaLive\Data\Storage::getInstance();
		$tmp = new \ManiaLivePlugins\ManiaHall\Controls\Player($storage->getPlayerObject($login)->nickName);
		$tmp->setReady($ready);
		$this->playerList[$login] = $tmp;
		$this->frame->addComponent($this->playerList[$login]);
	}
	
	function removePlayer($login)
	{
		$this->frame->removeComponent($this->playerList[$login]);
		unset($this->playerList[$login]);
	}
	
	function setPlayer($login, $ready)
	{
		if(array_key_exists($login, $this->playerList))
		{
			$this->playerList[$login]->setReady($ready);
		}
		else
		{
			$this->addPlayer($login, $ready);
		}
	}
}

?>
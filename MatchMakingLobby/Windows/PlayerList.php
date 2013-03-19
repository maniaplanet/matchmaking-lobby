<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9091 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-12 16:37:36 +0100 (mer., 12 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLivePlugins\MatchMakingLobby\Controls\Player;

class PlayerList extends \ManiaLive\Gui\Window
{

	protected $playerList = array();
	/**
	 * @var \ManiaLive\Gui\Controls\Pager
	 */
	protected $pager;

	protected function onConstruct()
	{
		$this->setSize(50, 100);

		$this->pager = new \ManiaLive\Gui\Controls\Pager();
		$this->pager->setSize(40, 100);
		$this->playerList = array();
		$this->addComponent($this->pager);
	}

	function addPlayer($login, $state = 0, $isAlly = false)
	{
		$storage = \ManiaLive\Data\Storage::getInstance();
		try
		{
			$playerObj = $storage->getPlayerObject($login);
			$tmp = new Player($playerObj ? $playerObj->nickName : $login);
		}
		catch(\Exception $e)
		{
			return;
		}
		$tmp->setState($state, $isAlly);
		$this->playerList[$login] = $tmp;
		$this->updateItemList();
	}

	function removePlayer($login)
	{
		if(array_key_exists($login, $this->playerList))
		{
			unset($this->playerList[$login]);
		}
		$this->updateItemList();
	}

	protected function updateItemList()
	{
		uasort($this->playerList,
			function (Player $p1, Player $p2)
			{
				if($p1->state == $p2->state)
				{
					if($p1->isAlly && $p2->isAlly)
					{
						return 0;
					}
					return $p1->isAlly ? -1 : 1;
				}
				return $p1->state > $p2->state ? -1 : 1;
			}
		);

		$this->pager->clearItems();
		foreach($this->playerList as $component)
			$this->pager->addItem($component);
	}

	function setPlayer($login, $state, $isAlly = false)
	{
		if(array_key_exists($login, $this->playerList))
		{
			$this->playerList[$login]->setState($state, $isAlly);
			$this->updateItemList();
		}
		else
		{
			$this->addPlayer($login, $state, $isAlly);
		}
	}

}

?>
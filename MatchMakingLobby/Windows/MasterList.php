<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

class MasterList extends AbstractPlayerList
{
	const MAX = 21;
	
	static protected $playerList = array();
	
	protected function onConstruct()
	{
		parent::onConstruct();
		$this->setAlign('left', 'center');
		
		$this->setPosition(-163, 0, 15);
		
		$this->dictionnary['title'] = 'masters';
		
		$this->orderList = false;
	}
	
	public static function addMaster($login, $nickName, $ladderPoints)
	{
		if (count(static::$playerList) >= static::MAX)
		{
			array_pop(static::$playerList);
		}
		static::addPlayer($login, $nickName, $ladderPoints, false, false);
		
		static::RedrawAll();
	}
}
?>
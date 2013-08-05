<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9091 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-12 16:37:36 +0100 (mer., 12 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

class PlayerList extends AbstractPlayerList
{
	public $smallCards = false;
	
	protected function onConstruct()
	{
		parent::onConstruct();
		$this->dictionnary['title'] = 'players';
	}

	function onDraw()
	{
		parent::onDraw();
	}
}

?>
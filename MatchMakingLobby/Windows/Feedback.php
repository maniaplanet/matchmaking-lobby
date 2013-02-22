<?php

/**
 * @copyright   Copyright (c) 2009-2013 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

class Feedback extends GiveUp
{
	protected function onConstruct()
	{
		parent::onConstruct();
		$this->message->setText('Feedback');
		$this->message->setUrl('http://forum.maniaplanet.com/viewtopic.php?f=435&t=16768&start=10');
	}
}

?>
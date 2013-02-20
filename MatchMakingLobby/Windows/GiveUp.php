<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9066 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-07 15:12:49 +0100 (ven., 07 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements\Bgs1InRace;
use ManiaLib\Gui\Elements\Button;

class GiveUp extends \ManiaLive\Gui\Window
{

	/** @var Button */
	private $message;

	protected function onConstruct()
	{
		$this->setSize(35, 7);

		$this->message = new Button(35, 7);
		$this->message->setAlign('center', 'center2');
		$this->message->setPosition(17.5, -5);
		$this->message->setText('Give Up');
		$this->addComponent($this->message);
	}

	function set($action)
	{
		$this->message->setAction($action);
	}

}

?>
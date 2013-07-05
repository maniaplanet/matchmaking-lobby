<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9091 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-12 16:37:36 +0100 (mer., 12 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Controls;

use ManiaLib\Gui\Elements;

class PlayerSmall extends Player
{

	function __construct($nickname)
	{
		parent::__construct($nickname);
		
		$this->setSize(45, 5);

		$this->bg->setBgcolor('111A');
	}
	
	function onDraw()
	{
		parent::onDraw();
		$this->echelonFrame->setPosition($this->sizeX - 2, 0.5);
	}
}

?>
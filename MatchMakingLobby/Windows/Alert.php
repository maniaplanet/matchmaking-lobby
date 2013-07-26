<?php

/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;

class Alert extends \ManiaLive\Gui\Window
{
	const SIZE_X = 200;
	const SIZE_Y = 80;
	
	/**
	 * @var Elements\Frame;
	 */
	protected $frameContent;
	
	function onConstruct()
	{
		$this->setLayer(\ManiaLive\Gui\Window::LAYER_CUT_SCENE);
		
		$this->frameContent = new Elements\Frame(static::SIZE_X, static::SIZE_Y);
		$this->frameContent->setPositionY(static::SIZE_Y/2-17);
		$this->frameContent->setValign('top');
		$this->frameContent->setLayout(new \ManiaLib\Gui\Layouts\Column(static::SIZE_X, static::SIZE_Y));
		$this->addComponent($this->frameContent);
	}
}

?>

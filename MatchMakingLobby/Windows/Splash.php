<?php

/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */
namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;

class Splash extends \ManiaLive\Gui\Window
{
	protected $backgroundUrl;
	
	protected $clickAction;
	
	protected $closeAction;

	/**
	 * @var Elements\Quad
	 */
	protected $background;
	
	/**
	 * @var Elements\Button
	 */
	protected $button;
	
	function onConstruct()
	{
		$this->background = new Elements\Quad(160, 90);
		$this->background->setAlign('center', 'center');
		$this->background->setBgcolor('000a');
		$this->addComponent($this->background);
		
		$this->button = new Elements\Button();
		$this->button->setHalign('center');
		$this->button->setPosY(-47);
		$this->button->setText('Close');
		$this->addComponent($this->button);
	}
	
	function setBackgroundUrl($url)
	{
		$this->backgroundUrl = $url;
	}
	
	function setBackgroundClickAction($action)
	{
		$this->clickAction = $action;
	}
	
	function setCloseAction($action)
	{
		$this->closeAction = $action;
	}
	
	function onDraw()
	{
		if($this->backgroundUrl)
		{
			$this->background->setImage($this->backgroundUrl);
		}
		if($this->clickAction)
		{
			$this->background->setAction($this->clickAction);
		}
		if($this->closeAction)
		{
			$this->button->setAction($this->closeAction);
		}
	}
}

?>

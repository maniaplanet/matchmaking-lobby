<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9108 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-13 17:15:36 +0100 (jeu., 13 déc. 2012) $:
 */

namespace ManiaLivePlugins\ManiaHall\Windows;

use ManiaLive\Features\Tick;
use ManiaLive\Event\Dispatcher;


class Label extends \ManiaLive\Gui\Window implements Tick\Listener
{
	protected $label;
	
	protected $countdown = 0;
	
	protected $message = '';
	
	protected $sound;


	protected function onConstruct()
	{
		$this->label = new \ManiaLib\Gui\Elements\Label(240);
		$this->label->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label->setScale(0.6);
		$this->label->setHalign('center');
		$this->addComponent($this->label);
		
		$this->sound = new \ManiaLib\Gui\Elements\Audio();
		$this->sound->setData('http://static.maniaplanet.com/manialinks/lobbyTimer.wav', true);
		$this->sound->setPosition(200);
		$this->sound->autoPlay();
	}
	
	function onTick()
	{
		$this->setMessage($this->message, --$this->countdown);
		$this->redraw();
	}


	function setMessage($message, $countdown = null)
	{
		$this->message = $message;
		$this->countdown = $countdown;
		$this->label->setText(sprintf($this->message, $countdown));
		
		if($this->countdown)
		{
			$this->addComponent($this->sound);
			Dispatcher::register(Tick\Event::getClass(), $this);
		}
		else
		{
			Dispatcher::unregister(Tick\Event::getClass(), $this);
			$this->removeComponent($this->sound);
		}
	}
}

?>
<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9108 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-13 17:15:36 +0100 (jeu., 13 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLive\Features\Tick;
use ManiaLive\Event\Dispatcher;

class Label extends \ManiaLive\Gui\Window implements Tick\Listener
{

	public $label;
	protected $countdown = 0;
	protected $message = '';
	protected $sound;

	protected function onConstruct()
	{		
		$this->label = new \ManiaLib\Gui\Elements\Label(400);
		$this->label->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label->setTextSize(5);
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
	
	function onDraw()
	{
		if($this->id !== 'animated-label')
		{
		\ManiaLive\Gui\Manialinks::appendXML('<script>#RequireContext CGameManialinkScriptHandler
#Include "MathLib" as MathLib&#13;
main() {&#13;
	declare CMlLabel label &lt;=&gt; (Page.MainFrame.GetFirstChild("animated-label") as CMlLabel);&#13;
	while(True) { label.Scale = 2+MathLib::Cos(CurrentTime*.002); yield; }&#13;
}</script>');
		}
	}

}

?>
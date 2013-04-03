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

class Label extends \ManiaLive\Gui\Window implements Tick\Listener
{

	public $label;
	public $label2;
	public $animated = false;
	public $hideOnF6 = true;
	protected $countdown = 0;
	protected $message = '';
	protected $sound;

	protected function onConstruct()
	{
		$this->label = new \ManiaLib\Gui\Elements\Label(400);
		$this->label->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label->setTextSize(5);
		$this->label->setHalign('center');
		$this->label->enableAutonewline();
		$this->label->setId('info-label');
		$this->addComponent($this->label);

		$this->label2 = new \ManiaLib\Gui\Elements\Label(400);
		$this->label2->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label2->setTextSize(5);
		$this->label2->setHalign('center');
		$this->label2->enableAutonewline();
		$this->label2->setText('Please wait...');
		$this->label2->setId('wait-label');
		$this->addComponent($this->label2);

		$this->sound = new \ManiaLib\Gui\Elements\Audio();
		$this->sound->setData('http://static.maniaplanet.com/manialinks/lobbyTimer.wav', true);
		$this->sound->setPosition(200);
		$this->sound->autoPlay();
	}

	function onDraw()
	{
		$animatedManiaScript = $this->animated ? 'True' : 'False';
		$hideOnF6ManiaScript = $this->hideOnF6 ? 'True' : 'False';
		$countdown = (int) $this->countdown;
		$countdownManiaScript = $countdown ? 'True' : 'False';
		$label = \ManiaLib\ManiaScript\Tools::escapeString($this->message);
		$this->setScript($label, $countdown, $countdownManiaScript, $animatedManiaScript, $hideOnF6ManiaScript);
	}

	function setMessage($message, $countdown = null)
	{
		$this->message = $message;
		$this->countdown = $countdown;

		if($this->countdown)
		{
			$this->addComponent($this->sound);
		}
		else
		{
			$this->removeComponent($this->sound);
		}
	}

	protected function setScript($label, $countdown, $countdownManiaScript, $animatedManiaScript, $hideOnF6ManiaScript)
	{
		\ManiaLive\Gui\Manialinks::appendScript(<<<MANIASCRIPT
#RequireContext CMlScript
#Include "MathLib" as MathLib
#Include "TextLib" as TextLib
main()
{
	declare Text labelText = "$label";
	declare Boolean animated = $animatedManiaScript;
	declare Boolean hideOnF6 = $hideOnF6ManiaScript;
	declare Boolean countdown = $countdownManiaScript;
	declare Integer countdownTime = CurrentTime;
	declare Integer countdownTimeLeft = $countdown;
	declare Boolean waiting = False;
	declare CMlLabel label <=> (Page.MainFrame.GetFirstChild("info-label") as CMlLabel);
	declare CMlLabel waitLabel  <=> (Page.MainFrame.GetFirstChild("wait-label") as CMlLabel);
	label.SetText(TextLib::Compose(labelText, TextLib::ToText(countdownTimeLeft)));
	waitLabel.Hide();

	while(True)
	{
		foreach(Event in PendingEvents)
		{
			if(Event.Type == CMlEvent::Type::KeyPress)
			{
				if(Event.KeyName == "F6")
				{
					if(hideOnF6 && !waiting)
					{
						waiting = True;
						label.Hide();
						waitLabel.Show();
					}
				}
			}
		}
		if(animated)
		{
			label.Scale = 2+MathLib::Cos(CurrentTime*.002);
		}
		if(countdownTimeLeft > 0 && CurrentTime - countdownTime > 1000)
		{
			countdownTime = CurrentTime;
			countdownTimeLeft = countdownTimeLeft - 1;
			label.SetText(TextLib::Compose(labelText, TextLib::ToText(countdownTimeLeft)));
		}
		yield;
	}
}
MANIASCRIPT
		);
	}
}

?>
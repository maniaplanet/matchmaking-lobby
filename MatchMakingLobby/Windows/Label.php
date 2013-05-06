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

	/** @var \ManiaLib\Gui\Elements\Label */
	public $label;

	/** @var \ManiaLib\Gui\Elements\Label */
	public $label2;

	/** @var \ManiaLib\Gui\Elements\Bgs1 */
	public $bg;

	/** @var bool */
	public $animated = false;

	/** @var bool */
	public $hideOnF6 = true;
	
	/** @var bool */
	public $showBackground = false;

	/** @var int */
	protected $countdown = 0;

	/** @var string */
	protected $message = '';

	/** @var \ManiaLib\Gui\Elements\Audio */
	protected $sound;

	protected function onConstruct()
	{
		$this->bg = new \ManiaLib\Gui\Elements\Bgs1(320, 20);
		$this->bg->setSubStyle(\ManiaLib\Gui\Elements\Bgs1::BgDialogBlur);
		$this->bg->setAlign('center', 'center2');
		$this->addComponent($this->bg);
		
		$this->label = new \ManiaLib\Gui\Elements\Label(240);
		$this->label->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label->setTextSize(5);
		$this->label->setAlign('center', 'center2');
		$this->label->enableAutonewline();
		$this->label->setId('info-label');
		$this->label->setTextid('text');
		$this->addComponent($this->label);

		$this->label2 = new \ManiaLib\Gui\Elements\Label(240);
		$this->label2->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label2->setTextSize(5);
		$this->label2->setAlign('center', 'center2');
		$this->label2->enableAutonewline();
		$this->label2->setText('Please wait...');
		$this->label2->setId('wait-label');
		$this->addComponent($this->label2);
		
		$this->sound = new \ManiaLib\Gui\Elements\Audio();
		$this->sound->setData('http://static.maniaplanet.com/manialinks/lobbies/timer.wav', true);
		$this->sound->setPosition(200);
		$this->sound->autoPlay();
	}

	function onDraw()
	{
		\ManiaLib\Gui\Manialink::appendXML(\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::build($this->message));
		$this->bg->setVisibility($this->showBackground);
		$this->setScript($this->countdown, $this->animated, $this->hideOnF6);
	}

	function setMessage($message, $countdown = null)
	{
		$this->message = $message;
		$this->countdown = $countdown;

		$this->sound->setVisibility($this->countdown !== null);
	}

	protected function setScript($countdown, $animated, $hideOnF6)
	{
		$animatedManiaScript = $animated ? 'True' : 'False';
		$hideOnF6ManiaScript = $hideOnF6 ? 'True' : 'False';
		$countdown = (int) $this->countdown;
		$countdownManiaScript = $countdown ? 'True' : 'False';

		\ManiaLive\Gui\Manialinks::appendScript(<<<MANIASCRIPT
#RequireContext CMlScript
#Include "MathLib" as MathLib
#Include "TextLib" as TextLib
main()
{
	declare Boolean animated = $animatedManiaScript;
	declare Boolean hideOnF6 = $hideOnF6ManiaScript;
	declare Boolean countdown = $countdownManiaScript;
	declare Integer countdownTime = CurrentTime;
	declare Integer countdownTimeLeft = $countdown;
	declare Boolean waiting = False;
	declare CMlLabel label <=> (Page.MainFrame.GetFirstChild("info-label") as CMlLabel);
	declare CMlLabel waitLabel  <=> (Page.MainFrame.GetFirstChild("wait-label") as CMlLabel);
	declare Text labelText = label.Value;
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
		if(countdown && countdownTimeLeft >= 0 && CurrentTime - countdownTime > 1000)
		{
			countdownTime = CurrentTime;
			countdownTimeLeft = countdownTimeLeft - 1;
			label.SetText(TextLib::Compose(labelText, TextLib::ToText(countdownTimeLeft)));
		}
		else if(countdown && countdownTimeLeft <= 0)
		{
			waiting = True;
			label.Hide();
			waitLabel.Show();
		}
		yield;
	}
}
MANIASCRIPT
		);
	}
}

?>
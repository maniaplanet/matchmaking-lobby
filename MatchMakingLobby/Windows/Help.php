<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: 9066 $:
 * @author      $Author: philippe $:
 * @date        $Date: 2012-12-07 15:12:49 +0100 (ven., 07 déc. 2012) $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Label as LegacyLabel;
use ManiaLive\Gui\Controls\Frame;
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

class Help extends \ManiaLive\Gui\Window
{

	public $displayHelp = true;
	public $modeName = '';

	protected $textLabel;

	protected $dico;

	protected function onConstruct()
	{
		$bullet = ' $<$ff0$o>$> ';

		$ui = new LegacyLabel(300);
		$ui->setPosition(0, -55);
		$ui->setStyle(LegacyLabel::TextRaceMessageBig);
		$ui->setTextSize(5);
		$ui->setHalign('center');
		$ui->setId('help-label');
		$ui->setTextid('switch');
		$this->addComponent($ui);

		$frame = new Frame();
		$frame->setId('help-frame');

		$ui = new Bgs1(340, 60);
		$ui->setPosition(-170, 0, -0.1);
		$ui->setSubStyle(Bgs1::BgDialogBlur);
		$frame->addComponent($ui);

		$this->textLabel = new LegacyLabel(200);
		$this->textLabel->setPosition(-140, -10);
		$this->textLabel->setStyle(LegacyLabel::TextRaceMessageBig);
		$this->textLabel->setTextSize(5);
		$this->textLabel->enableAutonewline();
		$this->textLabel->setId('help-label');
		$this->textLabel->setTextid('help');
		$frame->addComponent($this->textLabel);

		$allies = new Frame(80, 60);
		$allies->setPosition(100, -2);

		$ui = new LegacyLabel(70);
		$ui->setRelativeAlign('center');
		$ui->setAlign('center');
		$ui->setPosition(0, -3, 0.1);
		$ui->setTextid('tip');
		$ui->setStyle(LegacyLabel::TextTitle3);
		$allies->addComponent($ui);

		$ui = new \ManiaLib\Gui\Elements\Quad(70, 39);
		$ui->setRelativeAlign('center');
		$ui->setAlign('center');
		$ui->setPosition(0, -9, 0.1);
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/set-as-ally.bik', true);
		$allies->addComponent($ui);

		$ui = new LegacyLabel(70);
		$ui->setRelativeAlign('center');
		$ui->setAlign('center');
		$ui->setPosition(0, -50, 0.1);
		$ui->setTextColor('fff');
		$ui->setTextid('note');
		$allies->addComponent($ui);

		$frame->addComponent($allies);
		$this->addComponent($frame);
	}

	function onDraw()
	{
		$this->posZ = 3;

		$bullet = ' $<$ff0$o>$> ';
		$displayHelpManiaScript = $this->displayHelp ? 'True' : 'False';

		$this->dico = array(
			'switch' => 'helpSwitch',
			'help' => array('textId' => 'helpText', 'params' => array($bullet, $this->modeName)),
			'tip' => 'helpTip',
			'note' => 'helpNote',
		);

		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink($this->dico));
		\ManiaLive\Gui\Manialinks::appendScript(
			<<<MANIASCRIPT
#RequireContext CMlScript
#Include "MathLib" as MathLib
#Include "TextLib" as TextLib

main()
{
	declare Boolean helpDisplayed = $displayHelpManiaScript;
	declare CMlLabel helpLabel <=> (Page.MainFrame.GetFirstChild("help-label") as CMlLabel);
	declare CMlFrame helpFrame <=> (Page.MainFrame.GetFirstChild("help-frame") as CMlFrame);
	if(helpDisplayed)
	{
		helpLabel.Hide();
	}
	else
	{
		helpFrame.Hide();
	}

	while(True)
	{
		foreach(Event in PendingEvents)
		{
			if(Event.Type == CMlEvent::Type::KeyPress)
			{
				if(Event.KeyName == "F7")
				{
					if(helpDisplayed)
					{
						helpFrame.Hide();
						helpLabel.Show();
					}
					else
					{
						helpFrame.Show();
						helpLabel.Hide();
					}
					helpDisplayed = !helpDisplayed;
				}
				else if(Event.KeyName == "F6")
				{
					if (helpDisplayed)
					{
						helpFrame.Hide();
						helpLabel.Show();
					}
				}
			}
		}
		yield;
	}
}
MANIASCRIPT
		);
	}

}

?>
<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Views;

use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Elements\Quad;
use ManiaLib\Gui\Elements\Icons128x128_Blink;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Elements\Bgs1;

class CustomizedQuitDialog
{
	protected $displayedText;
	
	function __construct($displayedText)
	{
		$this->displayedText = $displayedText;
	}
	
	public function display()
	{
		Manialink::load();
		Manialink::appendXML(\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::build($this->displayedText));
		
		/*Manialink::appendScript(<<<MANIASCRIPT
#RequireContext CMlScript
#Include "MathLib" as MathLib
#Include "TextLib" as TextLib
main()
{
	declare Boolean countdown = True;
	declare Integer countdownTime = CurrentTime;
	declare Integer countdownTimeLeft = 10;
	declare CMlFrame frame  <=> (Page.MainFrame.GetFirstChild("block-quit") as CMlFrame);
	declare CMlLabel label <=> (Page.MainFrame.GetFirstChild("countdown-label") as CMlLabel);
	label.SetText(TextLib::ToText(countdownTimeLeft));

	while(True)
	{
		if(countdown && countdownTimeLeft >= 0 && CurrentTime - countdownTime > 1000)
		{
			countdownTime = CurrentTime;
			countdownTimeLeft = countdownTimeLeft - 1;
			label.SetText(TextLib::ToText(countdownTimeLeft));
		}
		else if(countdown && countdownTimeLeft <= 0)
		{
			frame.Hide();
		}
		yield;
	}
}
MANIASCRIPT
			);*/
		
		
		$frame = new \ManiaLib\Gui\Elements\Frame();
		$frame->setPosition(0, 5, 0);
		{
			$label = new Label(170);
			$label->setAlign('center','center2');
			$label->setStyle(Label::TextRaceMessageBig);
			$label->setTextSize(5);
			$label->setTextColor('f00');
			$label->setTextId('text');
			$frame->add($label);
			
			$iconBlink = new Icons128x128_Blink(15);
			$iconBlink->setAlign('right','center');
			$iconBlink->setPosition(-87, 0);
			$iconBlink->setSubStyle(Icons128x128_Blink::Hard);
			$frame->add($iconBlink);
			
			$iconBlink = new Icons128x128_Blink(15);
			$iconBlink->setAlign('left','center');
			$iconBlink->setPosition(87, 0);
			$iconBlink->setSubStyle(Icons128x128_Blink::Hard);
			$frame->add($iconBlink);
		}
		$frame->save();
		
	/*	$frame = new \ManiaLib\Gui\Elements\Frame();
		$frame->setPosition(0, -6.5, 10);
		$frame->setScriptEvents();
		$frame->setId('block-quit');
		{
			$bg = new Bgs1(180, 45);
			$bg->setSubStyle(Bgs1::BgDialogBlur);
			$bg->setAlign('center');
			$bg->setScriptEvents();
			$bg->setId('background');
			$bg->setManialink('');
			$frame->add($bg);
			
			$bg = new Quad(180, 45);
			$bg->setBgcolor('0008');
			$bg->setAlign('center');
			$bg->setScriptEvents();
			$frame->add($bg);
			
			$ui = new Label(120);
			$ui->setAlign('center');
			$ui->setPosition(0, -6, 0.1);
			$ui->setStyle(Label::TextRaceMessageBig);
			$ui->setTextSize(4);
			$ui->setText('Available in:');
			$frame->add($ui);
			
			$ui = new Label(20);
			$ui->setId('countdown-label');
			$ui->setAlign('center');
			$ui->setPosition(0, -15, 0.1);
			$ui->setStyle(Label::TextRaceMessageBig);
			$ui->setTextSize(5);
			$ui->setText('10');
			$frame->add($ui);
		}
		$frame->save();*/
		return Manialink::render(true);
	}

}

?>
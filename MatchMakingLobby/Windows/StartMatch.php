<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;
use ManiaLivePlugins\MatchMakingLobby\Services\Match;

class StartMatch extends \ManiaLive\Gui\Window
{

	/** @var Elements\Bgs1 */
	protected $background;
	
	/** @var Elements\Label */
	protected $label;
	
	/** @var Elements\Label */
	protected $transferLabel;
	
	/** @var Elements\Label */
	protected $cancelLabel;

	/** @var \ManiaLive\Gui\Controls\Frame */
	protected $team1;
	
	/** @var Elements\Quad */
	protected $team1Background;
	
	/** @var Elements\Quad */
	protected $team2Background;

	/** @var \ManiaLive\Gui\Controls\Frame */
	protected $team2;
	
	/** @var Elements\Label */
	protected $versus;

	/** @var Elements\Label */
	protected $message;
	protected $dico = array();
	
	/** @var Match */
	protected $match;
	
	protected $time;

	protected function onConstruct()
	{
		$this->background = new Elements\Quad(320, 142);
		$this->background->setAlign('center', 'center');
		$this->background->setImage('http://static.maniaplanet.com/manialinks/lobbies/background.png',true);
		$this->addComponent($this->background);
		
		$this->versus = new Elements\Label(70);
		$this->versus->setAlign('center', 'center2');
		$this->versus->setTextColor('fff7');
		$this->versus->setText('VS');
		$this->versus->setTextSize(50);
		$this->versus->setStyle(Elements\Label::TextTitle3);
		$this->addComponent($this->versus);
		
		$this->label = new Elements\Label(200, 20);
		$this->label->setPosY(47);
		$this->label->setAlign('center', 'center2');
		$this->label->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label->setTextid('text');
		$this->label->setId('info-label');
		$this->label->setTextSize(7);
		$this->addComponent($this->label);
		
		$this->transferLabel = clone $this->label;
		$this->transferLabel->setPosY(47);
		$this->transferLabel->setTextColor(null);
		$this->transferLabel->setTextid('transferText');
		$this->transferLabel->setId('transfer-label');
		$this->addComponent($this->transferLabel);
		
		$this->cancelLabel = new Elements\Label(200);
		$this->cancelLabel->setPosY(-47);
		$this->cancelLabel->setAlign('center', 'center2');
		$this->cancelLabel->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->cancelLabel->setTextColor('AAA');
		$this->cancelLabel->setTextid('cancel');
		$this->cancelLabel->setId('cancel-label');
		$this->cancelLabel->setTextSize(7);
		$this->addComponent($this->cancelLabel);

		$layout = new \ManiaLib\Gui\Layouts\Column();
		$layout->setMarginHeight(1);
		
		$this->team1 = new \ManiaLive\Gui\Controls\Frame();
		$this->team1->setLayout($layout);
		$this->team1->setPosition(-70);
		$this->addComponent($this->team1);
		
		$this->team2 = clone $this->team1;
		$this->team2->setPosX(70);
		$this->addComponent($this->team2);
	}
	
	function set(array $team1, array $team2, $time)
	{
		$this->team1->posY = count($team1) * 20 / 2;
		$this->team2->posY = count($team2) * 20 / 2;
		$this->addElements($team1, $this->team1);
		$this->addElements($team2, $this->team2);
		$this->time = $time;
		
	}
	
	function addElements(array $players, \ManiaLive\Gui\Controls\Frame $frame)
	{
		$playerCard = new \ManiaLivePlugins\MatchMakingLobby\Controls\PlayerDetailed();
		$playerCard->setAlign('center');
		
		foreach($players as $player)
		{
			$playerCard->nickname = $player->nickname;
			$playerCard->zone = $player->zone;
			$playerCard->rank = $player->rank;
			$playerCard->avatarUrl = 'file://Avatars/'.$player->login.'/Default';
			$playerCard->countryFlagUrl = $player->zoneFlag;
			$playerCard->echelon = $player->echelon;
			$frame->addComponent(clone $playerCard);
		}
	}
	
	protected function onDraw()
	{
		$this->posZ = 5;
		$countdown = (int)$this->time;
		
		\ManiaLive\Gui\Manialinks::appendScript(<<<MANIASCRIPT
#RequireContext CMlScript
#Include "MathLib" as MathLib
#Include "TextLib" as TextLib
main()
{
	declare Integer countdownTime = CurrentTime;
	declare Integer countdownTimeLeft = $countdown;
	declare Boolean waiting = False;
	declare CMlLabel label <=> (Page.MainFrame.GetFirstChild("info-label") as CMlLabel);
	declare CMlLabel label2 <=> (Page.MainFrame.GetFirstChild("transfer-label") as CMlLabel);
	declare CMlLabel label3 <=> (Page.MainFrame.GetFirstChild("cancel-label") as CMlLabel);
	declare Text labelText = label.Value;
	label.SetText(TextLib::Compose(labelText, TextLib::ToText(countdownTimeLeft)));
	label2.Hide();
	label3.Hide();

	while(True)
	{
		if(countdownTimeLeft > 0 && CurrentTime - countdownTime > 1000)
		{
			countdownTime = CurrentTime;
			countdownTimeLeft = countdownTimeLeft - 1;
			label.SetText(TextLib::Compose(labelText, TextLib::ToText(countdownTimeLeft)));
		}
		else if(countdownTimeLeft <= 0)
		{
			label2.Show();
			label.Hide();
			waiting = True;
		}
		yield;
	}
}
MANIASCRIPT
		);
		
		\ManiaLive\Gui\Manialinks::appendXML(
		\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::getInstance()->getManialink(array(
				'text' => 'launchMatch',
				'transferText' => 'transfer',
				'cancel' => 'cancel',
		)));
	}
	
	protected function secureNicknames(array $array)
	{
		return array_map(function ($e) { return '$<'.$e.'$>'; }, $array);
	}
	

}

?>

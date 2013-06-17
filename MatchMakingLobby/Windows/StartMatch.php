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
		
		$this->label = new Elements\Label(200, 20);
		$this->label->setAlign('center', 'center2');
		$this->label->setPosY(47);
		$this->label->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label->setTextColor('FF0');
		$this->label->setTextid('text');
		$this->label->setId('info-label');
		$this->label->setTextSize(7);
		$this->addComponent($this->label);
		
		$this->transferLabel = clone $this->label;
		$this->transferLabel->setTextColor(null);
		$this->transferLabel->setTextid('transferText');
		$this->transferLabel->setId('transfer-label');
		$this->addComponent($this->transferLabel);

		$layout = new \ManiaLib\Gui\Layouts\Column();
		$layout->setMarginHeight(1);
		
		$this->team1Background = new Elements\Quad(125, 20);
		$this->team1Background->setAlign('left', 'center');
		$this->team1Background->setPosition(-160);
		$this->team1Background->setBgcolor('0096');
		$this->addComponent($this->team1Background);
		
		$this->team2Background = new Elements\Quad(125, 20);
		$this->team2Background->setAlign('right', 'center');
		$this->team2Background->setPosition(160);
		$this->team2Background->setBgcolor('9006');
		$this->addComponent($this->team2Background);
		
		$this->team1 = new \ManiaLive\Gui\Controls\Frame();
		$this->team1->setLayout($layout);
		$this->team1->setPosition(-70);
		$this->addComponent($this->team1);
		
		$this->team2 = clone $this->team1;
		$this->team2->setPosX(70);
		$this->addComponent($this->team2);
		
		$ui = new Elements\Quad(25, 15);
		$ui->setAlign('center', 'center');
		$ui->setImage('http://static.maniaplanet.com/manialinks/lobbies/grey-quad.png', true);
		$this->addComponent($ui);

		$this->versus = new Elements\Label(30);
		$this->versus->setAlign('center', 'center2');
		$this->versus->setText('VS');
		$this->versus->setTextSize(7);
		$this->versus->setStyle(Elements\Label::TextRaceMessageBig);
		$this->addComponent($this->versus);
	}
	
	function set(array $team1, array $team2, $time)
	{
		$this->team1->posY = count($team1) * 20 / 2;
		$this->team2->posY = count($team2) * 20 / 2;
		$this->team1Background->setSizeY(count($team1) * 20);
		$this->team2Background->setSizeY(count($team2) * 20);
		$this->team1Background->setPosY(- 0.5 * (count($team1) -1));
		$this->team2Background->setPosY(- 0.5 * (count($team2) -1));
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
	declare Text labelText = label.Value;
	label.SetText(TextLib::Compose(labelText, TextLib::ToText(countdownTimeLeft)));
	label2.Hide();

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
				'blue' => 'blue',
				'red' => 'red',
				'text' => 'launchMatch',
				'transferText' => 'transfer',
		)));
	}
	
	protected function secureNicknames(array $array)
	{
		return array_map(function ($e) { return '$<'.$e.'$>'; }, $array);
	}
	

}

?>

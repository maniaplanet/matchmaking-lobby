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

	/** @var \ManiaLive\Gui\Controls\Frame */
	protected $team1;

	/** @var \ManiaLive\Gui\Controls\Frame */
	protected $team2;
	
	/** @var Elements\Label */
	protected $team1Label;
	
	/** @var Elements\Label */
	protected $team2Label;

	/** @var Elements\Label */
	protected $versus;

	/** @var Elements\Label */
	protected $message;
	protected $dico = array();
	
	/** @var Match */
	protected $match;

	protected function onConstruct()
	{
		$this->background = new Elements\Bgs1(360, 5);
		$this->background->setSubStyle(Elements\Bgs1::BgDialogBlur);
		$this->background->setAlign('center');
		$this->addComponent($this->background);

		$layout = new \ManiaLib\Gui\Layouts\Column();
		$layout->setMarginHeight(1);
		
		$this->team1 = new \ManiaLive\Gui\Controls\Frame();
		$this->team1->setLayout($layout);
		$this->team1->setPosition(-40, -12);
		$this->addComponent($this->team1);
		
		$this->team2 = clone $this->team1;
		$this->team2->setPosX(40);
		$this->addComponent($this->team2);

		$this->versus = new Elements\Label(20);
		$this->versus->setAlign('center', 'center2');
		$this->versus->setText('VS');
		$this->versus->setTextSize(7);
		$this->versus->setStyle(Elements\Label::TextRaceMessageBig);
		$this->addComponent($this->versus);
		
		$this->team1Label = new Elements\Label(35);
		$this->team1Label->setAlign('center','top');
		$this->team1Label->setPosition(-40, -3);
		$this->team1Label->setTextid('blue');
		$this->team1Label->setTextColor('00F');
		$this->team1Label->setTextSize(6);
		$this->team1Label->setStyle(Elements\Label::TextRaceMessageBig);
		$this->addComponent($this->team1Label);
		
		$this->team2Label = clone $this->team1Label;
		$this->team2Label->setPosX(40);
		$this->team2Label->setTextid('red');
		$this->team2Label->setTextColor('F00');
		$this->addComponent($this->team2Label);
	}
	
	function set(array $team1, array $team2)
	{
		$sizeY = 8 * max(count($team1), count($team2)) + 11;
		$this->background->setSizeY($sizeY);
		
		$this->versus->setPosY(- $sizeY / 2);

		$ui = new Elements\Label(50, 7);
		$ui->setAlign('center', 'top');
		$ui->setTextColor('fff');
		$ui->setTextSize(3);
		$ui->setStyle(Elements\Label::TextRankingsBig);
		
		foreach($team1 as $player)
		{
			$ui->setText($player);
			$this->team1->addComponent(clone $ui);
		}
		
		foreach($team2 as $player)
		{
			$ui->setText($player);
			$this->team2->addComponent(clone $ui);
		}
	}
	
	protected function onDraw()
	{
		$this->posZ = 5;
		
		\ManiaLive\Gui\Manialinks::appendXML(\ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary::build(array(
			'en' => array(
				'blue' => 'Blue',
				'red' => 'Red'
			),
			'fr' => array(
				'blue' => 'Bleu',
				'red' => 'Rouge'
			)
		)));
	}
	
	protected function secureNicknames(array $array)
	{
		return array_map(function ($e) { return '$<'.$e.'$>'; }, $array);
	}
	

}

?>

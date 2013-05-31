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
		$sizeY = 11 * max(count($team1), count($team2)) + 11;
		$this->background->setSizeY($sizeY);
		
		$this->versus->setPosY(- $sizeY / 2);

		$this->addElements($team1, $this->team1);
		$this->addElements($team2, $this->team2);
		
	}
	
	function addElements(array $players, \ManiaLive\Gui\Controls\Frame $frame)
	{
		$playerNickname = new Elements\Label(50, 4);
		$playerNickname->setAlign('center', 'top');
		$playerNickname->setTextColor('fff');
		$playerNickname->setTextSize(3);
		$playerNickname->setStyle(Elements\Label::TextRankingsBig);
		
		$playerRank = new Elements\Label(50, 5);
		$playerRank->setAlign('center', 'top');
		$playerRank->setTextSize(1);
		$playerRank->setTextEmboss();
		$playerRank->setStyle(Elements\Label::TextTips);
		
		foreach($players as $player)
		{
			$playerNickname->setText($player->nickname);
			$playerRank->setText(sprintf('%s: %s', $player->zone, ($player->rank > 0 ? $player->rank : '-')));
			$frame->addComponent(clone $playerNickname);
			$frame->addComponent(clone $playerRank);
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

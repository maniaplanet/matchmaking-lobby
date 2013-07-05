<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Controls;

use ManiaLib\Gui\Elements;

class PlayerDetailed extends \ManiaLive\Gui\Control
{
	public $zone;
	public $avatarUrl;
	public $login;
	public $nickname;
	public $rank;
	public $countryFlagUrl;
	public $echelon;
	
	/**
	 * @var Elements\Icons64x64_1
	 */
	protected $icon;

	/**
	 * @var Elements\Label
	 */
	protected $label;

	/**
	 * @var Elements\Label
	 */
	protected $rankLabel;
	
	/**
	 * @var Elements\Quad
	 */
	protected $countryFlag;
	
	/**
	 * @var Elements\Label
	 */
	protected $teamLabel;
	
	/**
	 * @var Elements\Quad
	 */
	protected $teamIcon;

	/**
	 * @var Elements\Quad
	 */
	protected $echelonQuad;

	/**
-	 * @var Elements\Label
	 */
	protected $echelonLabel;
	
	static function fromPlayer(\DedicatedApi\Structures\Player $p)
	{
		$ui = new static;
		$ui->avatarUrl = 'file://Avatars/'.$p->login.'/Default';
		$ui->nickname = $p->nickName;
		$path = explode('|', $p->path);
		$ui->zone = $path[1];
		$ui->rank = $p->ladderStats['PlayerRankings'][0]['Ranking'];
		return $ui;
	}

	function __construct()
	{
		$this->setSize(80, 20);

		$ui = new Elements\Quad(80, 20);
		$ui->setImage('http://static.maniaplanet.com/manialinks/elite/PlayerCardBg.dds',true);
		$this->addComponent($ui);

		$this->icon = new Elements\Icons64x64_1(20, 20);
		$this->icon->setBgcolor('F00');
		$this->addComponent($this->icon);

		$this->label = new Elements\Label(38);
		$this->label->setPosition(22, -2.5);
//		$this->label->setTextColor('fff');
		$this->label->setTextSize(3);
		$this->label->setStyle(Elements\Label::TextRaceMessage);
		$this->addComponent($this->label);
		
		$this->teamLabel = new Elements\Label(25);
		$this->teamLabel->setPosition(27, -11.5);
		$this->teamLabel->setValign('center2');
		$this->teamLabel->setStyle(Elements\Label::TextRaceMessage);
		$this->teamLabel->setTextSize(2);
		$this->teamLabel->setText('$o$09FLorem$z Ipsum Team');
//		$this->addComponent($this->teamLabel);
		
		$this->teamIcon = new Elements\Quad(4, 4);
		$this->teamIcon->setValign('center');
		$this->teamIcon->setPosition(22, -11.5);
		$this->teamIcon->setBgcolor('FF0a');
//		$this->addComponent($this->teamIcon);

		$this->rankLabel = new Elements\Label(30);
		$this->rankLabel->setAlign('left','center2');
		$this->rankLabel->setPosition(27, -17.5);
		$this->rankLabel->setText('-');
		$this->rankLabel->setStyle(Elements\Label::TextRaceMessage);
		$this->rankLabel->setTextSize(1);
		$this->addComponent($this->rankLabel);
		
		$this->countryFlag = new Elements\Quad(4, 4);
		$this->countryFlag->setAlign('left','center');
		$this->countryFlag->setPosition(22, -17.5);
		$this->addComponent($this->countryFlag);
		
		$frame = new \ManiaLive\Gui\Controls\Frame(72, 0);
		$frame->setScale(1.13);
		$this->addComponent($frame);
		
		$this->echelonQuad = new Elements\Quad(14.1551, 17.6938);
		$this->echelonQuad->setAlign('center', 'top');
		$frame->addComponent($this->echelonQuad);
		
		$ui = new Elements\Label(15);
		$ui->setAlign('center', 'top');
		$ui->setStyle(Elements\Label::TextRaceMessage);
		$ui->setPosition(-0.25, -3.6);
		$ui->setTextSize(0.5);
		$ui->setText('Echelon');
		$frame->addComponent($ui);
		
		$this->echelonLabel = new Elements\Label(10, 10);
		$this->echelonLabel->setAlign('center','center');
		$this->echelonLabel->setPosition(-0.25, -10.6);
		$this->echelonLabel->setStyle(Elements\Label::TextRaceMessageBig);
		$frame->addComponent($this->echelonLabel);
	}
	
	function onDraw()
	{
		$this->icon->setImage($this->avatarUrl, true);
		$this->label->setText($this->nickname);
		$this->rankLabel->setText(sprintf('%s: %d', $this->zone, $this->rank));
		$this->countryFlag->setImage($this->countryFlagUrl, true);
		
		$this->echelonQuad->setImage(sprintf('file://Media/Manialinks/Common/Echelons/echelon%d.dds',$this->echelon), true);
		$this->echelonLabel->setText($this->echelon);
	}
}

?>

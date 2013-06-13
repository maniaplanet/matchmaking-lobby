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
		$this->setSize(70, 20);

		$ui = new Elements\Quad(70, 20);
		$ui->setBgcolor('333');
		$this->addComponent($ui);

		$this->icon = new Elements\Icons64x64_1(20, 20);
		$this->icon->setSubStyle(Elements\Icons64x64_1::LvlRed);
		$this->icon->setPosition(0,0);
		$this->addComponent($this->icon);

		$this->label = new Elements\Label(34);
		$this->label->setPosition(21, -2.5);
		$this->label->setTextColor('fff');
		$this->label->setStyle(Elements\Label::TextCardMedium);
		$this->addComponent($this->label);

		$this->rankLabel = new Elements\Label(15);
		$this->rankLabel->setAlign('left','center2');
		$this->rankLabel->setPosition(26, -17.5);
		$this->rankLabel->setText('-');
		$this->rankLabel->setTextColor('fff');
		$this->rankLabel->setTextSize(1);
		$this->rankLabel->setScale(0.6);
		$this->addComponent($this->rankLabel);
		
		$this->countryFlag = new Elements\Quad(4, 3);
		$this->countryFlag->setAlign('left','center');
		$this->countryFlag->setPosition(21, -17.5);
		$this->addComponent($this->countryFlag);
		
		$ui = new Elements\Bgs1(14,24);
		$ui->setSubStyle(Elements\Bgs1::BgTitle3_1);
		$ui->setHalign('right');
		$ui->setPosition(72, 2);
		$this->addComponent($ui);
	}
	
	function onDraw()
	{
		$this->icon->setImage($this->avatarUrl, true);
		$this->label->setText($this->nickname);
		$this->rankLabel->setText(sprintf('%s: %d', $this->zone, $this->rank));
		$this->countryFlag->setImage('http://www.pepinieresbonnetfreres.be/Flags/france-flag.jpg', true);
	}
}

?>

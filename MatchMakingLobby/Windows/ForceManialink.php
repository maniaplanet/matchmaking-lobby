<?php
/**
 * @copyright   Copyright (c) 2009-2012 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Windows;

use ManiaLib\Gui\Elements;
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

class ForceManialink extends \ManiaLive\Gui\Window
{

	/** @var Elements\Xml*/
	protected $xml;
	
	/** @var Elements\Bgs1*/
	protected $bg;
	
	protected $displayMessage = true;
	
	/** @var Elements\Label */
	protected $label;
	
	protected function onConstruct()
	{
		$this->xml = new \ManiaLive\Gui\Elements\Xml();
		$this->addComponent($this->xml);
		
		$this->bg = new Elements\Bgs1(360, 20);
		$this->bg->setSubStyle(Elements\Bgs1::BgCardList);
		$this->bg->setAlign('center','center');
		$this->bg->setPosition(0, 0);
		$this->addComponent($this->bg);
		
		$this->label = new Elements\Label(120, 30);
		$this->label->setAlign('center','center');
		$this->label->setPosition(0, 0);
		$this->label->setStyle(\ManiaLib\Gui\Elements\Label::TextRaceMessageBig);
		$this->label->setTextSize(4);
		$this->label->setTextColor('0f0');
		$this->label->setTextid('text');
		$this->addComponent($this->label);
		
	}

	function set($to, $displayMessage = true)
	{
//		$this->xml->setContent('<script><!--main() { OpenLink("'.$to.'", CMlScript::LinkType::ManialinkBrowser); }--></script>');
		$this->displayMessage = $displayMessage;
	}
	
	function onDraw()
	{
		$this->posZ = 90;
		$this->removeComponent($this->bg);
		$this->removeComponent($this->label);
		if($this->displayMessage)
		{
			$this->addComponent($this->bg);
			$this->addComponent($this->label);
		}
		\ManiaLive\Gui\Manialinks::appendXML(Dictionary::getInstance()->getManiaLink(array('text' => 'transfer')));
	}

}

?>

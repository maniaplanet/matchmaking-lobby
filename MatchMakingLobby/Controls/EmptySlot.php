<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Controls;

use ManiaLib\Gui\Elements;
use ManiaLivePlugins\MatchMakingLobby\Utils\Dictionary;

class EmptySlot extends \ManiaLive\Gui\Control
{
	/**
	 * @var \ManiaLib\Gui\Elements\Bgs1
	 */
	protected $bg;
	/**
	 * @var Elements\Label
	 */
	protected $text;

	function __construct()
	{
		$this->setSize(80, 20);
		
		$this->bg = new Elements\Bgs1(80, 20);
		$this->bg->setSubStyle(Elements\Bgs1::BgListLine);
		$this->addComponent($this->bg);

		$this->text = new Elements\Label(80);
		$this->text->setAlign('center', 'center');
		$this->text->setPosition(40, -10);
		$this->text->setStyle(Elements\Label::TextButtonSmall);
		$this->text->setTextid('picked');
		$this->addComponent($this->text);
	}
	
	function getLabelTextid()
	{
		return $this->text->getTextid();
	}
	
	function onDraw()
	{
		$this->bg->setSize($this->sizeX, $this->sizeY);
		$this->text->setSize($this->sizeX - 5);
		$this->text->setPosition($this->sizeX / 2, - $this->sizeY / 2);
	}
	
	function destroy()
	{
		$this->destroyComponents();
		parent::destroy();
	}
	
	function onIsRemoved(\ManiaLive\Gui\Container $target)
	{
		parent::onIsRemoved($target);
		$this->destroy();
	}

}

?>

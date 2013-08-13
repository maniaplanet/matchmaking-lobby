<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Controls;

use ManiaLib\Gui\Elements;

class ButtonImage extends \ManiaLive\Gui\Control
{
	public $frame;
	
	/**
	 * @var Elements\Quad 
	 */
	public $bg;
	
	public $text;
	
	public function __construct($sizeX, $sizeY)
	{
		$this->setSize($sizeX, $sizeY);
		
		$this->frame = new \ManiaLive\Gui\Controls\Frame();
		
		$this->bg = new Elements\Quad($sizeX, $sizeY);
		$this->bg->setAlign('center', 'center');
		$this->frame->addComponent($this->bg);
		
		$this->text = new Elements\Label($sizeX, $sizeY);
		$this->text->setTextSize(1);
		$this->text->setStyle(Elements\Label::TextRaceMessageBig);
		$this->text->setOpacity(0.65);
		$this->text->setAlign('center', 'center2');
		$this->frame->addComponent($this->text);
		
		$this->addComponent($this->frame);
	}
	
	function onIsRemoved(\ManiaLive\Gui\Container $target)
	{
		parent::onIsRemoved($target);
		$this->destroy();
	}
}
?>
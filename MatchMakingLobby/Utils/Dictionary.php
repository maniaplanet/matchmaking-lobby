<?php
/**
 * @version     $Revision: $:
 * @author      $Author: $:
 * @date        $Date: $:
 */

namespace ManiaLivePlugins\MatchMakingLobby\Utils;

class Dictionary
{
	protected $script;
	
	protected $lang;
	
	static protected $instance;
	
	/**
	 * @return Translations
	 */
	static function getInstance($script = '')
	{
		if(!$script && !self::$instance)
		{
			throw new \InvalidArgumentException;
		}
		
		if(!self::$instance)
		{
			self::$instance = new self($script);
		}
		return self::$instance;
	}


	protected function __construct($script)
	{
		$this->script = $script;
		
		$folder = __DIR__.'/../Languages/';
		$files = scandir($folder);
		$pattern = sprintf('/%s-(\\w{2,3})\\.php/ixu', $script);
		foreach($files as $file)
		{
			$match = array();
			if(preg_match($pattern,$file, $match))
			{
				require_once $folder.$file;
				foreach($lang as $key => $text)
				{
					$this->lang[$key][$match[1]] = $text;
				}
				$lang = null;
			}
		}
	}
	
	/**
	 * @param array $textIds
	 */
	function getManiaLink(array $textIds)
	{
		$dictionnary = $this->getTexts($textIds);
		
		return self::build($dictionnary);
	}
	
	function getChat(array $textIds)
	{
		$dictionnary = $this->getTexts($textIds);
		
		$result = array();
		foreach($dictionnary as $language => $text)
		{
			if(is_array($text))
			{
				$text = implode('', $text);
			}
			$result[] = array('Lang' => $language, 'Text' => $text);
		}
		return $result;
	}
	
	protected function getTexts(array $textIds)
	{
		$dictionnary = array();

		$avalaibleLangagues = array_keys($this->lang);
		foreach($textIds as $outputTextId => $elements)
		{
			if(is_array($elements))
			{
				$textIds = explode('|', $elements['textId']);
			}
			else
			{
				$textIds = explode('|', $elements);
			}
			
			foreach($textIds as $text)
			{
				foreach($this->lang[$text] as $language => $string)
				{
					$dictionnary[$language][$outputTextId][] = $string;
				}
			}
			foreach($dictionnary as $language => $textIds)
			{
				foreach($textIds as $key => $values)
				{
					if(is_array($values))
					{
						$dictionnary[$language][$key] = implode('', $values);
					}
					if(is_array($elements) && array_key_exists('params', $elements))
					{
						$params = array();
						$params[] = $dictionnary[$language][$key];
						$params = array_merge($params, $elements['params']);
						$dictionnary[$language][$key] = call_user_func_array('sprintf', $params);
					}
				}
			}
		}
		return $dictionnary;
	}

	static function build(array $messages)
	{
		$dom = new \DOMDocument('1.0', 'utf-8');
		$dico = $dom->createElement('dico');
		$dom->appendChild($dico);
		foreach($messages as $language => $texts)
		{
			$lang = $dom->createElement('language');
			$lang->setAttribute('id', $language);
			$dico->appendChild($lang);

			foreach($texts as $key => $text)
			{
				$node = $dom->createElement($key);
				$node->appendChild($dom->createTextNode($text));
				$lang->appendChild($node);
			}
		}
		$output = $dom->saveXML();
		return $output;
	}
}

?>

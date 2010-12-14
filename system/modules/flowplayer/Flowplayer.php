<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Andreas Schempp 2010
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 * @version    $Id$
 */


class Flowplayer extends System
{
	
	/**
	 * Element ID
	 * @var string
	 */
	protected $strId;
	
	/**
	 * Path to Flowplayer
	 */
	protected $strPluginFolder = 'plugins/flowplayer';
	
	/**
	 * Configuration
	 * @var array
	 */
	protected $arrConfig = array();
	
	/**
	 * Commercial player
	 * @var bool
	 */
	protected $blnCommercial = true;
	
	/**
	 * Commercial license keys
	 * @var array
	 */
	protected $arrLicense;
	
	/**
	 * Flowplayer plugins
	 * @var array
	 */
	protected $arrPlugins = array();
	
	
	/**
	 * Construct and initialize object
	 * @param  array
	 */
	public function __construct($arrConfig=array())
	{
		parent::__construct();
		
		$this->strId = 'flowplayer_' . uniqid();
		$this->arrLicense = deserialize($GLOBALS['TL_CONFIG']['flowplayer_license'], true);
		
		if ($this->arrLicense[0] == '')
		{
			$this->arrLicense = array();
			$this->blnCommercial = false;
		}
		
		$this->enablePlugin('controls');
		
		foreach( $arrConfig as $strKey => $varValue )
		{
			$this->$strKey = $varValue;
		}
	}
	
	
	/**
	 * Set a value
	 * @param  string
	 * @param  mixed
	 * @return void
	 */
	public function __set($strKey, $varValue)
	{
		switch( $strKey )
		{
			case 'id':
				$this->strId = (string)$varValue;
				break;
			
			case 'pluginFolder':
				$varValue = trim($varValue, '/ ');
				if (is_file(TL_ROOT . '/' . $varValue . '/flowplayer.swf'))
				{
					$this->strPluginFolder = $varValue;
				}
				break;
			
			case 'plugins':
				break;
				
			case 'commercial':
				$this->blnCommercial = $varValue ? true : false;
				break;
			
			case 'license':
				if (is_array($varValue))
				{
					$this->arrLicense = $varValue;
				}
				else
				{
					$this->arrLicense[] = $varValue;
				}
				break;
			
			default:
				$this->arrConfig[$strKey] = $varValue;
				break;
		}
	}
	
	
	/**
	 * Get a value
	 * @param  string
	 * @return mixed
	 */
	public function __get($strKey)
	{
		switch( $strKey )
		{
			case 'id':
				return $this->strId;
			
			case 'pluginFolder':
				return $this->strPluginFolder;
				
			case 'commercial':
				return $this->blnCommercial;
			
			case 'license':
				return $this->arrLicense;
			
			default:
				return $this->arrConfig[$strKey];
		}
	}
	
	
	/**
	 * Generate Javascript
	 * @return string
	 */
	public function generate(array $arrConfig=null, array $arrFlash=null)
	{
		if (!is_array($arrConfig))
		{
			$arrConfig = $this->arrConfig;
		}
		
		// Allow flash player configuration
		if (is_null($arrFlash))
		{
			$strFlash = "'" . $this->strPluginFolder . "/flowplayer" . ($this->blnCommercial ? '.commercial' : '') . ".swf'";
		}
		else
		{
			$arrFlash['src'] = $this->strPluginFolder . "/flowplayer" . ($this->blnCommercial ? '.commercial' : '') . '.swf';
			$strFlash = $this->compileConfig($arrFlash);
		}
		
		if ($this->blnCommercial && count($this->arrLicense))
		{
			$arrConfig['key'] = $this->arrLicense;
		}
		
		$arrConfig['plugins'] = $this->arrPlugins;
		
		return "var " . $this->strId . " = flowplayer('" . $this->strId . "', $strFlash, " . $this->compileConfig($arrConfig) . ");";
		
		return $strBuffer;
	}
	
	
	/**
	 * Add flowplayer and plugin javascripts to TL_JAVASCRIPT
	 * @return void
	 */
	public function injectJavascript()
	{
		$GLOBALS['TL_JAVASCRIPT'][] = $this->strPluginFolder . '/flowplayer.js';
	}
	
	
	/**
	 * Enable a flowplayer plugin
	 * @param  string
	 * @param  array
	 * @param  bool
	 * @return bool
	 */
	public function enablePlugin($strName, array $arrConfig=array(), $blnMergeConfig=false, $strPluginFile=null)
	{
		if (is_null($strPluginFile))
		{
			$strPluginFile = $strName;
		}
		
		// Plugin must be installed
		if (!is_file(TL_ROOT . '/' . $this->strPluginFolder . '/flowplayer.' . $strName . '.swf'))
			return false;
		
		if (is_array($this->arrPlugins[$strName]) && $blnMergeConfig)
		{
			$arrConfig = array_merge($this->arrPlugins[$strName], $arrConfig);
		}
		
		$arrConfig['url'] = $this->strPluginFolder . '/flowplayer.' . $strName . '.swf';
		
		$this->arrPlugins[$strName] = $arrConfig;
		
		return true;
	}
	
	public function disablePlugin($strName)
	{
		if (!is_array($this->arrPlugins[$strName]))
			return false;
		
		unset($this->arrPlugins[$strName]);
		
		return true;
	}
	
	
	protected function compileConfig(array $arrConfig, $intLevel=0)
	{
		$blnNumerical = ctype_digit(implode('', array_keys($arrConfig)));
		$blnFirstArray = false;
		
		$arrCompile = array();
		
		foreach( $arrConfig as $strKey => $varValue )
		{
			if (is_array($varValue))
			{
				$blnFirstArray = true;
				
				// See http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
				if (ctype_digit(implode('', array_keys($varValue))))
				{
					$arrValues = array();
					$blnSecondArray = false;
					
					foreach( $varValue as $v )
					{
						if (is_array($v))
						{
							$blnSecondArray = true;
							$arrValues[] = $this->compileConfig($v, ($intLevel+1));
						}
						else
						{
							$arrValues[] = $this->convertString($v);
						}
					}
					
					if ($blnSecondArray)
					{
						$arrCompile[] = "'" . $strKey . "': [\n" . str_repeat("    ", ($intLevel+1)) . implode(",\n" . str_repeat("    ", ($intLevel+1)), $arrValues) . "\n" . str_repeat("    ", $intLevel+1) . "]";
					}
					else
					{
						$arrCompile[] = "'" . $strKey . "': [" . implode(", ", $arrValues) . "]";
					}
				}
				else
				{
					if (count($varValue))
					{
						$arrCompile[] = "'" . $strKey . "': \n" . str_repeat("    ", ($intLevel+1)) . $this->compileConfig($varValue, ($intLevel+1)) . "\n" . str_repeat("    ", $intLevel);
					}
					else
					{
						$arrCompile[] = "'" . $strKey . "': {}";
					}
				}
			}
			else
			{
				$arrCompile[] = ($blnNumerical ? '' : ("'" . $strKey . "': ")) . $this->convertString($varValue);
			}
		}
		
		if ($blnNumerical)
		{
			if ($blnFirstArray)
			{
				return "[\n" . str_repeat("    ", ($intLevel+1)) . implode(",\n" . str_repeat("    ", $intLevel+1), $arrCompile) . "\n" . str_repeat("    ", ($intLevel+1)) . "]";
			}
			else
			{
				return "[" . implode(",", $arrCompile) . "]";
			}
		}
		else
		{
			return "{\n" . str_repeat("    ", ($intLevel+1)) . implode(",\n" . str_repeat("    ", $intLevel+1), $arrCompile) . "\n" . str_repeat("    ", ($intLevel)) . "}";
		}
	}
	
	
	/**
	 * Add ' before and after a string (for javascript)
	 * @param  mixed
	 * @return string
	 */
	private function convertString($varValue)
	{
		if (is_numeric($varValue))
		{
			return (string)$varValue;
		}
		elseif (is_bool($varValue))
		{
			return $varValue ? 'true' : 'false';
		}
		else
		{
			// Boolean strings
			if ($varValue == 'true' || $varValue == 'false')
			{
				return $varValue;
			}
			
			// Javascript function
			elseif (strpos($varValue, 'function') === 0)
			{
				return $varValue;
			}
			
			return "'" . str_replace("'", "\'", $varValue) . "'";
		}
	}
}


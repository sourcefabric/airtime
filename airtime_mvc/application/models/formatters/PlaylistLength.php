<?php

class Format_PlaylistLength
{
	/**
	 * @string length
	 */
	private $_playlist;
	
	public function __construct($playlist)
	{
		$this->_playlist = $playlist;
	}
	
	public function getLength()
	{
		$formatter = new Format_HHMMSSULength($this->_playlist->getLength());
		$length = $formatter->format();
		
		if ($this->_playlist->isStatic()) {
			
			return $length;
		}
		else {
			
			return "~ {$length}";
		}
	}
}
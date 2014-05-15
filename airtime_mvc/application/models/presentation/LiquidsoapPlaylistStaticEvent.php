<?php

class Presentation_LiquidsoapPlaylistStaticEvent extends Presentation_LiquidsoapEvent
{
	private $_scheduledItem;

	/*
	 * @param CcSchedule $s
	*/
	public function __construct($s)
	{
		$this->_scheduledItem = $s;
	}

	public function createScheduleEvent(&$data)
	{
		
	}
	
}
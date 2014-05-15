<?php

class Presentation_LiquidsoapEventFactory
{
	public static function create($scheduleItem) {
		
		$type = $scheduleItem->getMediaItem()->getChildObject()->getType();
		$class = "Presentation_Liquidsoap{$type}Event";
		return new $class($scheduleItem);
	}
}
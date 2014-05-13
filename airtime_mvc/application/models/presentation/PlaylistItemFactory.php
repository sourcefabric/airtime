<?php

class Presentation_PlaylistItemFactory
{
	public static function create($item) {
		
		$media = $item->getMediaItem()->getChildObject();
		$type = $media->getType();
		
		$class = "Presentation_PlaylistItem" . $type;
		return new $class($item);
	}
}
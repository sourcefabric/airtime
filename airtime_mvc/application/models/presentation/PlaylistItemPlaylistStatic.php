<?php

class Presentation_PlaylistItemPlaylistStatic extends Presentation_PlaylistItem
{
	public function canEditCues() {
		return false;
	}

	public function canEditFades() {
		return false;
	}

	public function canPreview() {
		return false;
	}

	public function getTitle() {
		return $this->item->getName();
	}

	public function getCreator() {
		return $this->item->getCreator();
	}
}
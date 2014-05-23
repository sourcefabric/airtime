<?php

use Airtime\MediaItem\Playlist;

use Airtime\MediaItem\PlaylistPeer;

class Presentation_Playlist {
	
	const LENGTH_FORMATTER_CLASS = "Format_HHMMSSULength";
	
	public function __construct($playlist) {
		
		$this->playlist = $playlist;
	}
	
	public function getId() {
		
		return $this->playlist->getId();
	}
	
	public function getName() {
		
		return $this->playlist->getName();
	}
	
	public function getDescription() {
		
		return $this->playlist->getDescription();
	}
	
	public function getLastModifiedEpoch() {
		
		return $this->playlist->getUpdatedAt("U");
	}
	
	public function getLength() {
		
		$formatter = new Format_PlaylistLength($this->playlist);
		return $formatter->getLength();
	}
	
	public function hasContent() {
		
		$type = $this->playlist->getClassKey();
		
		return $type === intval(PlaylistPeer::CLASSKEY_0) ? true: false;
	}
	
	public function getContent() {
		
		if ($this->hasContent()) {
			return $this->playlist->getContents();
		}
	}
	
	public function getRules() {

		$rules = $this->playlist->getRules();
		
		$form = new Application_Form_PlaylistRules();
		$data = $form->buildForm($this->playlist, $rules);

		$form->populate($data);
		
		return $form;
	}
}
<?php

namespace Airtime;

use \Criteria;
use \PropelPDO;
use \Exception;
use \PropelException;
use \DateTimeZone;
use \DateTime;
use Airtime\om\BaseCcShowInstances;
use Airtime\CcScheduleQuery;
use \Propel;

/**
 * Skeleton subclass for representing a row from the 'cc_show_instances' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.airtime
 */
class CcShowInstances extends BaseCcShowInstances {

 /**
     * Get the [optionally formatted] temporal [starts] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                          If format is NULL, then the raw DateTime object will be returned.
     * @return     mixed Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     * @throws     PropelException - if unable to parse/validate the date/time value.
     */
    public function getDbStarts($format = 'Y-m-d H:i:s')
    {
        if ($this->starts === null) {
            return null;
        }

        try {
            $dt = new DateTime($this->starts, new DateTimeZone("UTC"));
        } catch (Exception $x) {
            throw new PropelException("Internally stored date/time/timestamp value could not be converted to DateTime: " . var_export($this->starts, true), $x);
        }

        if ($format === null) {
            // Because propel.useDateTimeClass is TRUE, we return a DateTime object.
            return $dt;
        } elseif (strpos($format, '%') !== false) {
            return strftime($format, $dt->format('U'));
        } else {
            return $dt->format($format);
        }
    }

    /**
     * Get the [optionally formatted] temporal [ends] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                          If format is NULL, then the raw DateTime object will be returned.
     * @return     mixed Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     * @throws     PropelException - if unable to parse/validate the date/time value.
     */
    public function getDbEnds($format = 'Y-m-d H:i:s')
    {
        if ($this->ends === null) {
            return null;
        }

        try {
            $dt = new DateTime($this->ends, new DateTimeZone("UTC"));
        } catch (Exception $x) {
            throw new PropelException("Internally stored date/time/timestamp value could not be converted to DateTime: " . var_export($this->ends, true), $x);
        }

        if ($format === null) {
            // Because propel.useDateTimeClass is TRUE, we return a DateTime object.
            return $dt;
        } elseif (strpos($format, '%') !== false) {
            return strftime($format, $dt->format('U'));
        } else {
            return $dt->format($format);
        }
    }

    /**
     * Get the [optionally formatted] temporal [last_scheduled] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                          If format is NULL, then the raw DateTime object will be returned.
     * @return     mixed Formatted date/time value as string or DateTime object (if format is NULL), NULL if column is NULL
     * @throws     PropelException - if unable to parse/validate the date/time value.
     */
    public function getDbLastScheduled($format = 'Y-m-d H:i:s')
    {
        if ($this->last_scheduled === null) {
            return null;
        }

        try {
            $dt = new DateTime($this->last_scheduled, new DateTimeZone("UTC"));
        } catch (Exception $x) {
            throw new PropelException("Internally stored date/time/timestamp value could not be converted to DateTime: " . var_export($this->last_scheduled, true), $x);
        }

        if ($format === null) {
            // Because propel.useDateTimeClass is TRUE, we return a DateTime object.
            return $dt;
        } elseif (strpos($format, '%') !== false) {
            return strftime($format, $dt->format('U'));
        } else {
            return $dt->format($format);
        }
    }

    //post save hook to update the cc_schedule status column for the tracks in the show.
    public function updateScheduleStatus(PropelPDO $con) {
        
        $this->updateDbTimeFilled($con);

        //scheduled track is in the show
        CcScheduleQuery::create()
            ->filterByDbInstanceId($this->id)
            ->filterByDbPlayoutStatus(0, Criteria::GREATER_EQUAL)
            ->filterByDbEnds($this->ends, Criteria::LESS_EQUAL)
            ->update(array('DbPlayoutStatus' => 1), $con);

        //scheduled track is a boundary track
        CcScheduleQuery::create()
            ->filterByDbInstanceId($this->id)
            ->filterByDbPlayoutStatus(0, Criteria::GREATER_EQUAL)
            ->filterByDbStarts($this->ends, Criteria::LESS_THAN)
            ->filterByDbEnds($this->ends, Criteria::GREATER_THAN)
            ->update(array('DbPlayoutStatus' => 2), $con);

        //scheduled track is overbooked.
        CcScheduleQuery::create()
            ->filterByDbInstanceId($this->id)
            ->filterByDbPlayoutStatus(0, Criteria::GREATER_EQUAL)
            ->filterByDbStarts($this->ends, Criteria::GREATER_THAN)
            ->update(array('DbPlayoutStatus' => 0), $con);
        
        $this->setDbLastScheduled(gmdate("Y-m-d H:i:s"));
        $this->save($con);
    }

    /**
     * 
     * This function resets the cc_schedule table's position numbers so that
     * tracks for each cc_show_instances start at position 1
     * 
     * The position numbers can become out of sync when the user deletes items
     * from linekd shows filled with dyanmic smart blocks, where each instance
     * has a different amount of scheduled items
     */
    public function correctSchedulePositions()
    {
        $schedule = CcScheduleQuery::create()
            ->filterByDbInstanceId($this->id)
            ->orderByDbStarts()
            ->find();

        $pos = 0;
        foreach ($schedule as $item) {
            $item->setDbPosition($pos)->save();
            $pos++;
        }
    }
    
    /**
     * Computes the value of the aggregate column time_filled
     *
     * @param PropelPDO $con A connection object
     *
     * @return mixed The scalar result from the aggregate query
     */
    public function computeDbTimeFilled(PropelPDO $con)
    {
        $stmt = $con->prepare('SELECT SUM(clip_length) FROM "cc_schedule" WHERE cc_schedule.INSTANCE_ID = :p1');
        $stmt->bindValue(':p1', $this->getDbId());
        $stmt->execute();
        return $stmt->fetchColumn();
    }
    
    /**
     * Updates the aggregate column time_filled
     *
     * @param PropelPDO $con A connection object
     */
    public function updateDbTimeFilled(PropelPDO $con)
    {
        $timefilled = $this->computeDbTimeFilled($con);
        if(is_null($timefilled)){
            $timefilled = "00:00:00";
        }
        $this->setDbTimeFilled($timefilled);
        $this->save($con);
    }
    
    public function preInsert(PropelPDO $con = null) {
        $now = new DateTime("now", new DateTimeZone("UTC"));
        $this->setDbCreated($now);
        return true;
    }

    public function isRecorded()
    {
        return $this->getDbRecord() == 1 ? true : false;
    }

    public function isRebroadcast()
    {
        return $this->getDbRebroadcast() == 1 ? true : false;
    }
    
    /*
     * @param $now epoch seconds, useful if comparing several shows.
     * 
     * returns true if this show instance is currently playing
     */
    public function isCurrentShow($epochNow = null)
    {
    	if (is_null($epochNow)) {
            $epochNow = microtime(true);
        }
        
        $epochStart = floatval($this->getDbStarts('U.u'));
        $epochEnd = floatval($this->getDbEnds('U.u'));
        
        if ($epochStart < $epochNow && $epochEnd > $epochNow) {
            return true;
        }
        
        return false;
    }
    
    public function isLinked()
    {
    	$show = $this->getCcShow();
    	return $show->isLinked();
    }

    public function getLocalStartDateTime()
    {
        $startDT = $this->getDbStarts(null);
        return $startDT->setTimezone(new DateTimeZone(Application_Model_Preference::GetTimezone()));
    }
    
    //populates content in cc_schedule by unrolling all playlists.
    public function unroll()
    {
    	$con = Propel::getConnection();
    	
    	$scheduled = array();
    	
    	$scheduledItems = CcScheduleQuery::create()
	    	->filterByCcShowInstances($this)
	    	->orderByDbStarts()
	    	->find($con);
    	
    	foreach($scheduledItems as $scheduleItem) {
    	
    		$media = $scheduleItem->getMediaItem()->getChildObject();
    		
    		if (substr($media->getType(), 0, 8) == "Playlist") {
    			 
    			$scheduled = array_merge($scheduled, $media->getScheduledContent($con));
    		}
    		else {
    			
    			$scheduled[] = array (
    				"id" => $scheduleItem->getDbMediaId(),
    				"cliplength" => $scheduleItem->getDbClipLength(),
    				"cuein" => $scheduleItem->getDbCueIn(),
    				"cueout" => $scheduleItem->getDbCueOut(),
    				"fadein" => $scheduleItem->getDbFadeIn(),
    				"fadeout" => $scheduleItem->getDbFadeOut(),
    			);
    		}
    	}
    	
    	$scheduledItems->delete();
    	//clear the old objects.
    	$this->clearCcSchedules();
    	
    	$crossfade = \Application_Model_Preference::GetDefaultCrossfadeDuration();
    	
    	$showStartDT = $this->getDbStarts(null);
    	$showEndDT = $this->getDbEnds(null);
    	$startDT = $showStartDT;
    	$position = 0;
    	$utcTimezone = new DateTimeZone("UTC");
    	
    	foreach ($scheduled as $scheduleEntry) {
    		
    		$item = new CcSchedule();
    		$item->setDbStarts($startDT);
    		$item->setDbMediaId($scheduleEntry["id"]);
    		$item->setDbCueIn($scheduleEntry["cuein"]);
    		$item->setDbCueOut($scheduleEntry["cueout"]);
    		$item->generateCliplength();
    		$item->setDbFadeIn($scheduleEntry["fadein"]);
    		$item->setDbFadeOut($scheduleEntry["fadeout"]);
    		$item->setCcShowInstances($this);
    		$item->setDbPosition($position);
    		
    		$cliplength = $item->getDbClipLength();
    		
    		$startEpoch = $startDT->format("U.u");
    		$durationSeconds = \Application_Common_DateHelper::playlistTimeToSeconds($cliplength);
    		
    		//add two float numbers to 6 subsecond precision
    		//DateTime::createFromFormat("U.u") will have a problem if there is no decimal in the resulting number.
    		$endEpoch = bcadd($startEpoch , (string) $durationSeconds, 6);
    		$endDT = DateTime::createFromFormat("U.u", $endEpoch, $utcTimezone);
    		
    		$item->setDbEnds($endDT);
    		
    		//set the playout status of this item.
    		if ($endDT < $showEndDT) {
    			$playoutstatus = 1;
    		}
    		else if ($startDT < $showEndDT && $endDT > $showEndDT) {
    			$playoutstatus = 2;
    		}
    		else {
    			$playoutstatus = 0;
    		}
    		
    		$item->setDbPlayoutStatus($playoutstatus);
    		
    		$item->save();

    		//decrease end time by crossfade duration for next start time.
    		$newStartEpoch = bcsub($endEpoch, $crossfade, 6);
    		$startDT = DateTime::createFromFormat("U.u", $newStartEpoch, $utcTimezone);
    		
    		$position++;
    	}
    	
    	$this->setDbLastScheduled(new DateTime("now", $utcTimezone));
    	$timefilled = $this->computeDbTimeFilled($con);
    	if (is_null($timefilled)){
    		$timefilled = "00:00:00";
    	}
    	$this->setDbTimeFilled($timefilled);
    	$this->save();
    }

} // CcShowInstances

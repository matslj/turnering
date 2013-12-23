<?php

// ===========================================================================================
//
// Class: CDate
//
// 
// Author: Mats Ljungquist
//
class CDate {

    private $timestamp;
    private $date;
    private $hour;
    private $minute;

    private function __construct($timestamp) {
        $this->timestamp = $timestamp;
        $this->date = date('Y-m-d', $timestamp);
        $this->hour = date('H', $timestamp);
        $this->minute = date('i', $timestamp);
    }
    
    public static function getInstanceFromMysqlDatetime($mysqlDate) {
        $retDate = null;
        $phpdate = null;
        if ($mysqlDate == null) {
            $phpdate = time();
        } else {
            $phpdate = strtotime($mysqlDate);
        }
        if ($phpdate) {
            $retDate = new self($phpdate);
        }
        return $retDate;
    }

    // ------------------------------------------------------------------------------------
	//
	// Destructor
	//
	public function __destruct() {
	}
    
    public function getTimestamp() {
        return $this->timestamp;
    }

    public function getDate() {
        return $this->date;
    }

    public function getHour() {
        return $this->hour;
    }

    public function getMinute() {
        return $this->minute;
    }

} // End of Of Class

?>
<?php

// ===========================================================================================
//
// Class: CMatch
//
// 
// Author: Mats Ljungquist
//
class CScoreProxy {

    private $id;
    private $diffLow;
    private $diffHigh;
    private $scorePlayerOne;
    private $scorePlayerTwo;

    function __construct($id, $diffLow, $diffHigh, $scorePlayerOne, $scorePlayerTwo) {
        $this->id = $id;
        $this->diffLow = $diffLow;
        $this->diffHigh = $diffHigh;
        $this->scorePlayerOne = $scorePlayerOne;
        $this->scorePlayerTwo = $scorePlayerTwo;
    }
    
    public function getCopy() {
        $tempObject = new CScoreProxy($this->id, $this->diffLow, $this->diffHigh, $this->scorePlayerOne, $this->scorePlayerTwo);
        return $tempObject;
    }
    
    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getDiffLow() {
        return $this->diffLow;
    }

    public function setDiffLow($diffLow) {
        $this->diffLow = $diffLow;
    }

    public function getDiffHigh() {
        return $this->diffHigh;
    }

    public function setDiffHigh($diffHigh) {
        $this->diffHigh = $diffHigh;
    }

    public function getScorePlayerOne() {
        return $this->scorePlayerOne;
    }

    public function setScorePlayerOne($scorePlayerOne) {
        $this->scorePlayerOne = $scorePlayerOne;
    }

    public function getScorePlayerTwo() {
        return $this->scorePlayerTwo;
    }

    public function setScorePlayerTwo($scorePlayerTwo) {
        $this->scorePlayerTwo = $scorePlayerTwo;
    }

} // End of Of Class

?>
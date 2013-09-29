<?php

// ===========================================================================================
// CUserData
//
// Description: 
// Class for storing user data.
//
// Author: Mats Ljungquist
//
class user_CUserData {
    
    private $id;
    private $account;
    private $name;
    private $email;
    private $avatar;
    private $idGroup;
    private $army;
    private $active;
    
    private $totalScore;

    function __construct($id, $account, $name, $email, $avatar, $idGroup, $army, $active) {
        $this->id = $id;
        $this->account = $account;
        $this->name = $name;
        $this->email = $email;
        $this->avatar = $avatar;
        $this->idGroup = $idGroup;
        $this->army = $army;
        $this->active = $active;
    }

    public function __destruct() {
        ;
    }
    
    public function getCopy() {
        $ud = $this;
        $retVal = new user_CUserData($ud->getId(), $ud->getAccount(), $ud->getName(), $ud->getEmail(), $ud->getAvatar(), $ud->getIdGroup(), $ud->getArmy(), $ud->getActive());
        return $retVal;
    }
    
    static function cmp($playerA, $playerB) {
        if ($playerA->getTotalScore() == $playerB->getTotalScore()) {
            return 0;
        }
        return ($playerA->getTotalScore() > $playerB->getTotalScore()) ? -1 : 1;
    }
    
    public static function getEmptyInstance() {
        $retVal = new self(0, "", "", "", "", "", "", "");
        return $retVal;
    }
    
    public function equals($theUser) {
        if (empty($theUser)) {
            return false;
        }
        return ($theUser->getId() == $this->id);
    }
    
    public function isEmptyInstance() {
        return $this->id == 0;
    }
    
    public function getId() {
        return $this -> id;
    }

    public function setId($id) {
        $this -> id = $id;
    }

    public function getAccount() {
        return $this -> account;
    }

    public function setAccount($account) {
        $this -> account = $account;
    }

    public function getName() {
        return $this -> name;
    }

    public function setName($name) {
        $this -> name = $name;
    }

    public function getEmail() {
        return $this -> email;
    }

    public function setEmail($email) {
        $this -> email = $email;
    }

    public function getAvatar() {
        return $this -> avatar;
    }

    public function setAvatar($avatar) {
        $this -> avatar = $avatar;
    }

    public function getIdGroup() {
        return $this -> idGroup;
    }

    public function setIdGroup($idGroup) {
        $this -> idGroup = $idGroup;
    }
    
    public function isAdmin() {
        return strcmp($this->idGroup, 'adm') == 0;
    }
    
    public function isUser($aUserId) {
        return $aUserId === $this -> id;
    }
    
    public function getArmy() {
        return $this->army;
    }

    public function setArmy($army) {
        $this->army = $army;
    }

    public function getActive() {
        return $this->active;
    }

    public function setActive($active) {
        $this->active = $active;
    }

    public function getTotalScore() {
        return $this->totalScore;
    }

    public function setTotalScore($totalScore) {
        $this->totalScore = $totalScore;
    }

}

?>
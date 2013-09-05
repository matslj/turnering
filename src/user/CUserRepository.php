<?php

// ===========================================================================================
// user_CUserRepository
//
// Description: 
// This class is responsible for storing all active users in a session variable
// for system wide access. If no users are present in session memory, users will be
// read into memory from the database.
// 
// REMEMBER: __autoload before session_start(); Otherwise odd errors might occur.
//
// Author: Mats Ljungquist
//
class user_CUserRepository {
    
    private $users;

    private function __construct($theUsers) {
        $this->users = $theUsers;
    }

    public function __destruct() {
        ;
    }
    
    public static function getInstance($theDatabase) {
        // DB connection is required
        if (empty($theDatabase)) {
            return null;
        }

        $tempUsers = self::repopulateWithUsersFromDB($theDatabase);

        return new self($tempUsers);
    }

    public static function repopulateWithUsersFromDB($theDatabase) {
        // Get the tablenames
        $tableUser       = DBT_User;
        $tableGroup      = DBT_Group;
        $tableGroupMember  = DBT_GroupMember;

        $query = <<< EOD
            SELECT
                    idUser,
                    accountUser,
                    nameUser,
                    lastLoginUser,
                    emailUser,
                    armyUser,
                    activeUser,
                    avatarUser,
                    idGroup,
                    nameGroup
            FROM {$tableUser} AS U
                    INNER JOIN {$tableGroupMember} AS GM
                            ON U.idUser = GM.GroupMember_idUser
                    INNER JOIN {$tableGroup} AS G
                            ON G.idGroup = GM.GroupMember_idGroup
            WHERE deletedUser = FALSE;
EOD;

         $res = $theDatabase->Query($query);
         $tempUsers = array();
         while($row = $res->fetch_object()) {
             $tempActive = $row->activeUser == 1 ? true : false;
             $tempUsers[$row->idUser] = new user_CUserData($row->idUser, $row->accountUser, $row->nameUser, $row->emailUser, $row->avatarUser, $row->idGroup, $row->armyUser, $tempActive);
         }

         return $tempUsers;
    }

    public function getUser($idUser) {
        return $this->users[$idUser];
    }

    public function getUsers() {
        return $this->users;
    }
    
    public function getActiveUsers() {
        $retUsers = array();
        foreach ($this->users as $value) {
            if($value->getActive() == true) {
                $retUsers[] = $value;
            }
        }
        return $retUsers;
    }
    
}

?>
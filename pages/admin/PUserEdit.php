<?php
// ===========================================================================================
//
// PProfileProcess.php
//
// Updates user password, email or avatar.
// 
// @author Mats Ljungquist
//

$log = logging_CLogger::getInstance(__FILE__);

// -------------------------------------------------------------------------------------------
//
// Get pagecontroller helpers. Useful methods to use in most pagecontrollers
//
$pc = CPageController::getInstance();

// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();

$intFilter->FrontControllerIsVisitedOrDie();
$intFilter->UserIsSignedInOrRecirectToSignIn();
// Check so that logged in user is admin
$intFilter->IsUserMemberOfGroupAdminOrTerminate();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$user 		= $pc->POSTisSetOrSetDefault('accountid', 0);
$accountName 	= $pc->POSTisSetOrSetDefault('accountname', null);
$name 	= $pc->POSTisSetOrSetDefault('name', '');
$army 	= $pc->POSTisSetOrSetDefault('army', 'nonsense');
$action	= $pc->POSTisSetOrSetDefault('action', '');
$active	= $pc->POSTisSetOrSetDefault('active', 'false');

// Check incoming data
$pc->IsNumericOrDie($user, 0);

// Create database object (to get the required sql-config)
$db = new CDatabaseController();
$mysqli = $db->Connect();

// Get user-object
$uRep = user_CUserRepository::getInstance($db);
$uo = $uRep->getUser($user);
$okToChangeAdmin = WS_CHANGE_PASSWORD_ON_ADMIN;
if ($uo != null && $uo->isAdmin() && !$okToChangeAdmin && strcmp($uo->getAccount(), $accountName) != 0) {
    $_SESSION['errorMessage']	= "Den här applikationen har blivit konfigurerad till att inte acceptera förändring av adminusername";
} else {

// Sanitize data
if ($accountName != null) {
    $accountName = $mysqli->real_escape_string($accountName);
}
$name = $mysqli->real_escape_string($name);

if (strcmp($active, "true") != 0) {
    $active = "false";
}

$query = '';

if (!CHTMLHelpers::isSelectableArmy($army)) {
    $army = '';
}
// Kolla vilken action som gäller och definiera query utifrån detta
if (strcmp($action, 'edit') == 0) {
    $spSetUserNameAndEmail = DBSP_SetTournamentUser;
    $query = "CALL {$spSetUserNameAndEmail}({$user}, '{$accountName}', '{$name}', '{$army}', {$active});";
} else if (strcmp($action, 'create') == 0) {
    if (empty($accountName)) {
        $_SESSION['errorMessage'] = "Fel: användarnamn måste innehålla värde";
    } else {
        $spCreateUserAccountOrEmail = DBSP_CreateUserAccountTournament;
        $query = "CALL {$spCreateUserAccountOrEmail}('{$accountName}', '{$name}', '{$army}', '{$accountName}', {$active});";
    }
} else if (strcmp($action, 'delete') == 0) {
    $spDeleteUser = DBSP_DeleteUser;
    $query = "CALL {$spDeleteUser}({$user});";
} else {
    die("Bad command. Very bad.");
}
}

// Errors exist - Exit back to the userlist page
if (!empty($_SESSION['errorMessage'])) {
    $pc->RedirectTo($pc->POSTisSetOrSetDefault('redirect'));
    exit;
}

// Perform the query
$res = $db->MultiQuerySpecial($query);
if ($res != null) {
    // Ignore results but count successful statements.
    $nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();
    $log -> debug("Number of statements: " . $nrOfStatements);

    // Kolla vilken action som gäller och kolla hur det gick utfrån detta
    if (strcmp($action, 'edit') == 0) {
        if($nrOfStatements != 1) {
            $_SESSION['errorMessage']	= "Fel: kunde inte uppdatera användare";
        }
    } else if (strcmp($action, 'create') == 0) {
        if($nrOfStatements != 2) {
            $_SESSION['errorMessage']	= "Fel: kunde inte skapa användare";
        }
    } else if (strcmp($action, 'delete') == 0) {
        if($nrOfStatements != 1) {
            $_SESSION['errorMessage']	= "Fel: kunde inte radera användare";
        }
    }
}

$mysqli->close();

// -------------------------------------------------------------------------------------------
//
// Redirect to another page
//
$pc->RedirectTo($pc->POSTisSetOrSetDefault('redirect'));
exit;

?>
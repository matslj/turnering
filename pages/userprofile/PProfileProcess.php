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

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//
$user 		= $pc->POSTisSetOrSetDefault('accountid', '');
$intFilter -> IsUserMemberOfGroupAdminOrIsCurrentUserOrTerminate($user);

$password 	= $pc->POSTisSetOrSetDefault('password1', '');
$passwordAgain 	= $pc->POSTisSetOrSetDefault('password2', '');
$email 	= $pc->POSTisSetOrSetDefault('email', '');
$army 	= $pc->POSTisSetOrSetDefault('army', '');
$avatar = $pc->POSTisSetOrSetDefault('avatar', '');
$gravatar = $pc->POSTisSetOrSetDefault('gravatar', '');
$typeOfSubmit = $pc->POSTisSetOrSetDefault('submit', '');

// Create database object (to get the required sql-config)
$db = new CDatabaseController();
// Get the SP names
$spChangePassword = DBSP_SetUserPassword;
$spChangeEmail = DBSP_SetUserEmail;
$spChangeAvatar = DBSP_SetUserAvatar;
$spChangeArmy = DBSP_SetUserArmy;
$spChangeGravatar = DBSP_SetUserGravatar;
$query = "";
$mysqli = $db->Connect();

// Get user-object
$uRep = user_CUserRepository::getInstance($db);
$uo = $uRep->getUser($user);
$okToChangeAdmin = WS_CHANGE_PASSWORD_ON_ADMIN;

if ($uo->isAdmin() && !$okToChangeAdmin) {
    $_SESSION['errorMessage']	= "Den här applikationen har blivit konfigurerad till att inte acceptera förändringar på adminkontot";
} else {
// What type of submit are we dealing with?
// Avalible types:
// 1) Change password - for changing password
// 2) Change email - for change of email
// 3) Change avatar - for change of avatar link
//
// Default throws an error (but not an exception)
switch ($typeOfSubmit) {
    case "change-password":
        // $log -> debug("change-password");
        if (empty($password) || empty($passwordAgain)) {
            $_SESSION['errorMessage']	= "Lösenordsfälten får inte vara tomma";
        } else if (strcmp($password, $passwordAgain) != 0) {
            $_SESSION['errorMessage']	= "Lösenordsfälten måste matcha varandra";
        } else {
            $password = $mysqli->real_escape_string($password);
            // Create the query
            $query = "CALL {$spChangePassword}({$user}, '{$password}');";
        }
        break;
    case "change-email":
        $email = $mysqli->real_escape_string($email);
        // Create the query
        $query = "CALL {$spChangeEmail}({$user}, '{$email}');";
        break;
    case "change-avatar":
        $log -> debug("change-avatar");
        $avatar = $mysqli->real_escape_string($avatar);
        // Create the query
        $query = "CALL {$spChangeAvatar}({$user}, '{$avatar}');";
        break;
    case "change-gravatar":
        $log -> debug("change-avatar");
        $gravatar = $mysqli->real_escape_string($gravatar);
        // Create the query
        $query = "CALL {$spChangeGravatar}({$user}, '{$gravatar}');";
        break;
    case "change-army":
        $army = $mysqli->real_escape_string($army);
        if (!CHTMLHelpers::isSelectableArmy($army)) {
            $army = '';
        }
        // Create the query
        $query = "CALL {$spChangeArmy}({$user}, '{$army}');";
        break;
    default:
       $log -> debug("nada");
       $_SESSION['errorMessage'] = "I don't know what you just did, but I do not like it.";
}
}

if (empty($_SESSION['errorMessage'])) {
    $log ->debug("query: " . $query);
    // Perform the query
    $res = $db->MultiQuery($query);
    // Ignore results but count successful statements.
    $nrOfStatements = $db->RetrieveAndIgnoreResultsFromMultiQuery();
    $log -> debug("Number of statements: " . $nrOfStatements);
    // Must be exactly one successful statement.
    if($nrOfStatements != 1) {
        $_SESSION['errorMessage']	= "Kunde inte uppdatera databasen.";
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
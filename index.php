<?php

// ===========================================================================================
//
// index.php
//
// An implementation of a PHP frontcontroller for a web-site.
//
// All requests passes through this page, for each request is a pagecontroller choosen.
// The pagecontroller results in a response or a redirect.
//
// -------------------------------------------------------------------------------------------
//
// Require the files that are common for all pagecontrollers.
//

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');

//
// start a timer to time the generation of this page (excluding config.php)
//
if(WS_TIMER) {
	$gTimerStart = microtime(TRUE);
}

//
// Enable autoload for classes. User PEAR naming scheme for classes.
// E.G captcha_CCaptcha as classname.
//
function __autoload($class_name) {
    $path = str_replace('_', DIRECTORY_SEPARATOR, $class_name);
    require_once(TP_SOURCEPATH . "$path.php");
}
session_start();
// Allow only access to pagecontrollers through frontcontroller
// $indexIsVisited = TRUE;

// -------------------------------------------------------------------------------------------
//
// Redirect to the choosen pagecontroller.
// Observe that all redirects in all subpages should consist of either the
// gPage attribute or the gSubPage-attribute. So no hard referencs in subpages.
//
$gPage = isset($_GET['p']) ? $_GET['p'] : 'home';
$gSubPage = '';

// In order to use modules this snippet is used
$gSubPages = explode("_", $gPage);
if (count($gSubPages) >= 2) {
    $gPage = $gSubPages[0];   // Pages accessed from this index file
    $gSubPage = $gSubPages[1]; // Pages accessed from a sub level index file
}

switch ($gPage) {
    //
    // Hem
    // changing from PIndex.php to forum/PIndex.php
    //
    case 'home':             require_once(TP_PAGESPATH . 'PHome.php'); break;
    case 'about':            require_once(TP_PAGESPATH . 'PAbout.php'); break;

    //
    // Install database
    //
    case 'install':          require_once(TP_PAGESPATH . 'install/PInstall.php'); break;
    case 'installp':         require_once(TP_PAGESPATH . 'install/PInstallProcess.php'); break;

    //
    // Login
    //
    case 'login':            require_once(TP_PAGESPATH . 'login/PLoginSimple.php'); break;
    case 'loginp':           require_once(TP_PAGESPATH . 'login/PLoginProcess.php'); break;
    case 'logoutp':          require_once(TP_PAGESPATH . 'login/PLogoutProcess.php'); break;

    //
    // User profile
    //
    case 'profile':          require_once(TP_PAGESPATH . 'userprofile/PProfileShow.php'); break;
    case 'profilep':         require_once(TP_PAGESPATH . 'userprofile/PProfileProcess.php'); break;

    //
    // Admin pages
    //
    case 'admin':            require_once(TP_PAGESPATH . 'admin/index.php'); break;

    //
    // Page updater
    //
    case 'page-edit':		 require_once(TP_PAGESPATH . 'page/PPageEdit.php'); break;
    case 'page-save':		 require_once(TP_PAGESPATH . 'page/PPageSave.php'); break;
    
    //
    // Pages generated as PDF
    //
    case 'pdfscoreboard':	 require_once(TP_ROOT . 'pdf/PDFscoreboard.php'); break;
    case 'pdfmatchup':		 require_once(TP_ROOT . 'pdf/PDFmatchup.php'); break;
    
    //
    // Matches
    //
    case 'matchup':          require_once(TP_PAGESPATH . 'page/PPairingOfMatches.php'); break;
    case 'matchupp':         require_once(TP_PAGESPATH . 'page/PPairingOfMatchesProcess.php'); break;
    case 'matchupap':        require_once(TP_PAGESPATH . 'page/PPairingOfMatchesActionProcess.php'); break;
    case 'scoreboard':       require_once(TP_PAGESPATH . 'page/PScoreboard.php'); break;
    
    // 
    // Tournaments in general
    //
    case 'past':    require_once(TP_PAGESPATH . 'page/PPlayedTournament.php'); break;
    case 'sbd':    require_once(TP_PAGESPATH . 'part/PScoreboard.php'); break;
    
    // 
    // User tournaments
    //
    case 'mytournaments':    require_once(TP_PAGESPATH . 'page/PTournaments.php'); break;
    case 'mytournamentsp':   require_once(TP_PAGESPATH . 'page/PTournamentsProcess.php'); break;
    
    //
    // Default case, trying to access some unknown page, should present some error message
    // or show the home-page
    //
    default:                 require_once(TP_PAGESPATH . 'P404.php'); break;
}
?>

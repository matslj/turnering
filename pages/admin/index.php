<?php
// ===========================================================================================
//
// index.php
//
// Modulecontroller. An implementation of a PHP module frontcontroller (module controller). 
// This page is called from the global frontcontroller. Its function could be named a 
// sub-frontcontroller or module frontcontroller. I call it a modulecontroller.
//
// All requests passes through this page, for each request a pagecontroller is choosen.
// The pagecontroller results in a response or a redirect.
//
// Author: Mats Ljungquist
//

// -------------------------------------------------------------------------------------------
//
// Redirect to the choosen pagecontroller.
//
global $gSubPages;
$thePage = "home";
if (count($gSubPages) >= 2) {
    $thePage = $gSubPages[1]; // Module 1 level down
}

switch($thePage) {
    //
    // User management pages
    //
    case 'home': require_once(TP_PAGESPATH . 'admin/PAdminIndex.php');
    break;
    case 'anvandare': require_once(TP_PAGESPATH . 'admin/PUsersList.php');
    break;
    case 'anvandarep': require_once(TP_PAGESPATH . 'admin/PUserEdit.php');
    break;

    // Tournament management pages
    case 'tournament': require_once(TP_PAGESPATH . 'admin/PTournament.php');
    break;
    case 'tournamentp': require_once(TP_PAGESPATH . 'admin/PTournamentProcess.php');
    break;
    case 'tournamentpd': require_once(TP_PAGESPATH . 'admin/PTournamentPointFilterDialogProcess.php');
    break;

	//
    // Default case, trying to access some unknown page, should present some error message
    // or show the home-page
    //
    default: require_once(TP_PAGESPATH . 'P404.php');
        break;
}


?>
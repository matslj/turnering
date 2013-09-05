<?php
// ===========================================================================================
//
// PLogin.php
//
// Show a login-form, ask for user name and password.
//
// Author: Mats Ljungquist
//

// $log = logging_CLogger::getInstance(__FILE__);

// -------------------------------------------------------------------------------------------
//
// Get pagecontroller helpers. Useful methods to use in most pagecontrollers
//
$pc = CPageController::getInstance(FALSE);

// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();
$intFilter->FrontControllerIsVisitedOrDie();

// -------------------------------------------------------------------------------------------
//
// Always redirect to latest visited page on success.
//
$redirectTo = $pc->SESSIONisSetOrSetDefault('history1');
$history2 = $pc->SESSIONisSetOrSetDefault('history2');

// Define variables
$title = "Inloggning";
$buttonText = ">>>&nbsp;&nbsp;Logga in";
// Link to images
$imageLink = WS_IMAGES;

// -------------------------------------------------------------------------------------------
//
// Show the login-form
//
$htmlRight = "";
$htmlLeft = "";

$htmlMain = <<<EOD
<h1>{$title}</h1>
<div id='login'>
<form action="?p=loginp" method="post">
    <input type='hidden' name='redirect' value='{$redirectTo}'>
    <input type='hidden' name='history1' value='{$redirectTo}'>
    <input type='hidden' name='history2' value='{$history2}'>
    <fieldset>
            <label for="nameUser">Användarnamn: <span class="ico"><img src="{$imageLink}/user.png" alt="ikon användarnamn" border="0" /></span></label>
            <input id="nameUser" class="login" type="text" name="nameUser" required autofocus>
            <label for="passwordUser">Lösenord: <span class="ico"><img src="{$imageLink}/pass.png" alt="ikon lösenord" border="0" /></span></label>
            <input id="passwordUser" class="password" type="password" name="passwordUser" required>
    </fieldset>
    <fieldset style='margin-top: 20px;'>
        <!-- <span class="password"><a href="#">Forgot Password</a></span> -->
        <button type="submit" name="submit">{$buttonText}</button>
    </fieldset>
</form>
</div> <!-- #login -->
EOD;

// -------------------------------------------------------------------------------------------
//
// Create and print out the resulting page
//
$page = new CHTMLPage();

$page->printPage('Inloggning', $htmlLeft, $htmlMain, $htmlRight);
exit;
?>
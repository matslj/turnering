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
// $pc->LoadLanguage(__FILE__);

// -------------------------------------------------------------------------------------------
//
// Interception Filter, controlling access, authorithy and other checks.
//
$intFilter = new CInterceptionFilter();
$intFilter->FrontControllerIsVisitedOrDie();

// -------------------------------------------------------------------------------------------
//
// Take care of _GET/_POST variables. Store them in a variable (if they are set).
//

// -------------------------------------------------------------------------------------------
//
// Always redirect to latest visited page on success.
//
$redirectTo = $pc->SESSIONisSetOrSetDefault('history1');
$history2 = $pc->SESSIONisSetOrSetDefault('history2');

// Define variables
$title = "Inloggning";
$buttonText = ">>> Logga in";
$captcha = captcha_CCaptcha::getInstance("");

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
            <label for="nameUser">Användarnamn: </label>
            <input id="nameUser" class="login" type="text" name="nameUser">
            <label for="passwordUser">Lösenord: </label>
            <input id="passwordUser" class="password" type="password" name="passwordUser">
    </fieldset>
    <fieldset>
        <span class="password"><a href="#">Forgot Password</a></span>
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
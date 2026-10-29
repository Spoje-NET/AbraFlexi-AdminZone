<?php
$I = new AcceptanceTester($scenario);
$I->wantTo('See Anonymous Login Page');
$I->amOnPage('/customerlogin.php');
$I->seeElement("form[name=\"Login\"]");
$I->cantSee('$text');
$I->cantSee('Error');
$I->cantSee('Warning');
$I->cantSee('Notice');

$I->wantTo('Try to login nonexistent user');
$I->fillField("form input[name=\"username\"]", "nonexist");
$I->fillField("form input[name=\"password\"]", "nonexist");
$I->click("form button.btn.btn-success");
$I->waitForElement("#StatusMessages", 3);
$I->see('Bylo zadáno chybné uživatelské jméno či heslo');

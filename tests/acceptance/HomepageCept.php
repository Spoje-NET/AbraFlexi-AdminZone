<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('See Anonymous homepage');
$I->amOnPage('/');
$I->seeElement(".mpicon");
$I->cantSee('$text');
$I->cantSee('Error');
$I->cantSee('Warning');
$I->cantSee('Notice');

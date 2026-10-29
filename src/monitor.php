<?php

//  python runner.py php -f ParujFakturyNew2Old.php

namespace AdminZone;

/**
 * AdminZone - Stránka kontaktu.
 *
 * @author     Vítězslav Dvořák <vitex@arachne.cz>
 * @copyright  2018 Spoje.Net
 */

require_once 'includes/Init.php';

//system('killall python2');
//exec('python2 ./bin/runner.py ./bin/colorlog > /dev/null 2>&1 &');

$oPage->onlyForUser();
$oPage->addItem(new ui\PageTop(_('Monitor')));

$oPage->includeJavaScript('js/ansi_up.js');

$oPage->addJavaScript('

function sleep(milliseconds) {
  var start = new Date().getTime();
  for (var i = 0; i < 1e7; i++) {
    if ((new Date().getTime() - start) > milliseconds){
      break;
    }
  }
}

sleep(3000);

var ansi_up = new AnsiUp;

window.webSocket = new WebSocket("ws://' . $_SERVER['SERVER_ADDR'] . ':9999");
window.webSocket.onmessage = function(e) {
    console.log("Got echo: " + e.data);
    $("#output").append( ansi_up.ansi_to_html(e.data) );
    window.scrollBy(0, 1000);
}
');

$oPage->addItem(new \Ease\Html\PreTag(
    _('Please wait ...') . "\n",
    ['id' => 'output', 'style' => 'background-color: lightgray;']
));

$oPage->draw();

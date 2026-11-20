<?php

declare(strict_types=1);

// Debian autoloader for abraflexi-adminzone

require_once '/usr/share/php/AbraFlexi/autoload.php';
require_once '/usr/share/php/AbraFlexiBricks/autoload.php';
require_once '/usr/share/php/EaseTWB5/autoload.php';
require_once '/usr/share/php/EaseTWB5Widgets/autoload.php';
require_once '/usr/share/php/EaseTWB5WidgetsAbraFlexi/autoload.php';
require_once '/usr/share/php/EaseHtmlWidgets/autoload.php';
require_once '/usr/share/php/AbraFlexiUiDataTables/autoload.php';

// Extension packages (abraflexi-adminzone-ipex, -subreg, -sledovanitv) ship an
// autoloader fragment here; the core does not depend on their libraries.
foreach (glob('/usr/share/abraflexi-adminzone/extensions/autoload-*.php') ?: [] as $extensionAutoload) {
    require_once $extensionAutoload;
}

spl_autoload_register(function (string $class): void {
    // longest prefixes first
    $mappings = [
        'AdminZone\\ui\\' => '/usr/share/abraflexi-adminzone/AdminZone/ui/',
        'AdminZone\\Importer\\' => '/usr/share/abraflexi-adminzone/AdminZone/importer/',
        'AdminZone\\Zabbix\\' => '/usr/share/abraflexi-adminzone/AdminZone/Zabbix/',
        'AdminZone\\' => '/usr/share/abraflexi-adminzone/AdminZone/',
    ];
    foreach ($mappings as $prefix => $baseDir) {
        if (strncmp($prefix, $class, \strlen($prefix)) !== 0) {
            continue;
        }
        $file = $baseDir.str_replace('\\', '/', substr($class, \strlen($prefix))).'.php';
        if (file_exists($file)) {
            require $file;

            return;
        }
    }
});

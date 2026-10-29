# AbraFlexi AdminZone

Administrátorská zóna pro [AbraFlexi](https://www.abraflexi.eu/): správa zákazníků, fakturace, párování plateb,
upomínky, zpracování webhooků, dashboardy a integrace (IPEX, Subreg, Redmine, ISPConfig, Zabbix).

Vznikla vyčleněním administrátorské části z projektu `system.spoje.net`; zákaznická část žije v
[AbraFlexi-ClientZone](https://github.com/VitexSoftware/AbraFlexi-ClientZone).

*Administration portal for AbraFlexi operators – counterpart of ClientZone.*

## Instalace

    composer install
    cp .env.example .env     # doplňte přístupové údaje
    php -S localhost:8080 -t src

Přihlášení: `adminlogin.php` – přihlašovací údaje a token ověřuje AbraFlexi (`/login-logout/login`). Aplikace nepoužívá žádnou vlastní databázi; stav webhooků je v `STATE_DIR`.

Debian balíček: `abraflexi-adminzone` (`debuild -i -us -uc -b`), konfigurace v `/etc/abraflexi-admin-zone/.env`.

## Licence

MIT, viz `LICENSE`. Přibalené knihovny v `src/assets` a `src/js` mají vlastní licence.

## Struktura

- `src/` – stránky, `src/AdminZone/` – třídy (`AdminZone\`), `src/bin/` – CLI skripty
- `i18n/` – gettext doména `adminzone`
- `debian/` – balíčkování

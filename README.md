# AbraFlexi AdminZone

Administrátorská zóna pro [AbraFlexi](https://www.abraflexi.eu/): správa zákazníků a přiřazení
jejich účtů ve službách IPEX, Subreg a SledovaniTV (EXT:id). Párování plateb, upomínky, zpracování webhooků a digesty
jsou samostatné projekty (`abraflexi-matcher`, `abraflexi-reminder`, `abraflexi-webhook-acceptor`, `DigestModules`).

Vznikla vyčleněním administrátorské části z projektu `system.spoje.net`; zákaznická část žije v
[AbraFlexi-ClientZone](https://github.com/VitexSoftware/AbraFlexi-ClientZone).

*Administration portal for AbraFlexi operators – counterpart of ClientZone.*

## Instalace

    composer install
    cp .env.example .env     # doplňte přístupové údaje
    php -S localhost:8080 -t src

Přihlášení: `login.php` – přihlašovací údaje a token ověřuje AbraFlexi (`/login-logout/login`). Aplikace nepoužívá žádnou vlastní databázi.

Debian balíček: `abraflexi-adminzone` (`debuild -i -us -uc -b`), konfigurace v `/etc/abraflexi-admin-zone/.env`.

## Licence

MIT, viz `LICENSE`. Přibalené knihovny v `src/assets` a `src/js` mají vlastní licence.

## Struktura

- `src/extids.php` – přiřazení účtů IPEX / Subreg / SledovaniTV k zákazníkům AbraFlexi (EXT:id)
- `src/AdminZone/` – třídy (`AdminZone\`): `ExtIdService` a služby, `User` (přihlášení tokenem AbraFlexi)
- `src/clients.php`, `src/adresar.php` – seznam klientů a karta adresáře
- `i18n/` – gettext doména `adminzone`
- `debian/` – balíčkování

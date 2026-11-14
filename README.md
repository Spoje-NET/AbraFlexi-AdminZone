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

## Kontrola ceníku

Stránka `pricelist.php` (menu *Ceník*) zkontroluje položky ceníku, zda je
[ClientZone](https://github.com/VitexSoftware/AbraFlexi-ClientZone) umí správně prodávat.
ClientZone nabízí položky se štítky `ESHOP` a `AKTIVNI`; z objednané položky vznikne
objednávka (typ zásoby *služba*) nebo záloha (*zboží*, *materiál*, *poplatek*), jiné typy odmítne.

| Závažnost | Kontrola | Význam |
|---|---|---|
| chyba | `unsupported-type` | nabízená položka má typ zásoby, který ClientZone nezpracuje |
| chyba | `no-name`, `negative-price` | chybí název, záporná cena |
| chyba | `expired`, `not-yet-valid` | nabízená položka platí jen do/od jiného roku (`platiDo`, `platiOd`) |
| chyba | `credit-unit-price` | kredit (`KREDIT_DOMENA`) má jednotkovou cenu jinou než 1 |
| chyba | `missing-product`, `product-not-offered` | produkt, na kterém obchod stojí, v ceníku chybí nebo ho zákazník neuvidí |
| varování | `zero-price`, `no-vat`, `no-unit` | nabízená položka je zdarma, nemá sazbu DPH nebo měrnou jednotku (u služby určuje fakturační období) |
| varování | `duplicate-code`, `code-spaces` | stejný kód u více položek (bez ohledu na velikost písmen a mezery), mezery kolem kódu |
| upozornění | `half-labelled`, `no-description`, `code-with-space` | jen jeden ze štítků, chybí popis, mezera v kódu |

Položky bez štítků `ESHOP` a `AKTIVNI` se posuzují jen z hlediska chyb dat (název, záporná cena,
duplicity); pravidla obchodu na ně neplatí. Výpis jde filtrovat podle závažnosti.

Produkty, které obchod musí mít, se zadávají v `.env` (nepovinné, bez nastavení se jejich
existence nekontroluje):

    PRICELIST_EXPECTED_PRODUCTS="MULTIFLEXI,HOSTING_WEB,HOSTING_MAIL,KREDIT_DOMENA,KREDIT_VOIP"

Kódy používají order pluginy ClientZone. `platiOd` a `platiDo` se berou jako roky; hodnoty `0`
a `9999` znamenají bez omezení.

## Licence

MIT, viz `LICENSE`. Přibalené knihovny v `src/assets` a `src/js` mají vlastní licence.

## Struktura

- `src/extids.php` – přiřazení účtů IPEX / Subreg / SledovaniTV k zákazníkům AbraFlexi (EXT:id)
- `src/AdminZone/` – třídy (`AdminZone\`): `ExtIdService` a služby, `User` (přihlášení tokenem AbraFlexi)
- `src/clients.php`, `src/adresar.php` – seznam klientů a karta adresáře
- `src/pricelist.php` – kontrola ceníku (`AdminZone\PricelistChecker`, `ui\PricelistReport`)
- `i18n/` – gettext doména `adminzone`
- `debian/` – balíčkování

# AbraFlexi AdminZone

[English](README.md) | **Česky**

Administrátorská zóna pro [AbraFlexi](https://www.abraflexi.eu/): správa zákazníků a přiřazení
jejich účtů ve službách IPEX, Subreg a SledovaniTV (EXT:id). Párování plateb, upomínky, zpracování webhooků a digesty
jsou samostatné projekty (`abraflexi-matcher`, `abraflexi-reminder`, `abraflexi-webhook-acceptor`, `DigestModules`).

Vznikla vyčleněním administrátorské části z projektu `system.spoje.net`; zákaznická část žije v
[AbraFlexi-ClientZone](https://github.com/VitexSoftware/AbraFlexi-ClientZone).

## Co umí

- **Klienti** – seznam klientů, úprava adresy a kontaktů, **karta klienta** (viz níže)
- **Služby** – párování účtů IPEX, Subreg a SledovaniTV se zákazníky AbraFlexi, registrace kontaktu v Subreg a SledovaniTV
- **Ceník a nabídka pro ClientZone** – kontrola ceníku, strom a editor položek, **obrázky produktů** (otočení, ořez, nahrazení)
- **Vyhledávání** napříč evidencemi AbraFlexi

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

### Editor ceníku a strom

Stránka **Ceník → Strom a položky** (`pricelisttree.php`) ukazuje strom ceníku AbraFlexi
(evidence `strom`, kořen `STR_CEN`) s počty položek a u vybrané větve tabulku položek: typ zásoby,
štítky a zda je zákazníci ClientZone uvidí. **Editor** (`pricelistitem.php`) nabízí výběr místo
ručního psaní kódů:

- **typ zásoby** – ClientZone prodá službu (vznikne přijatá objednávka) a zboží, materiál nebo
  poplatek (vznikne zálohová faktura); ostatní typy editor odmítne u položky, která se nabízí,
- **štítky** z číselníku štítků ceníku, řídící `ESHOP` a `AKTIVNI` první,
- **místo ve stromu** (`strom-cenik`) – ClientZone nabízí položky pod svou katalogovou větví,
- měrná jednotka, skupina zboží, sazba DPH a platnost.

Katalogová větev ClientZone (jeho nastavení `PROD_CAT`) se zadá i v `.env` AdminZone, aby editor
poznal položky mimo nabídku (bez nastavení se bere celý strom):

    PROD_CAT=1

### Obrázky produktů

U uložené položky (`pricelistitem.php?id=…`) je panel **Obrázek**:

- **otočení** doleva, doprava a o 180°,
- **ořez** – tažením myší přes obrázek se vybere oblast, lze zvolit poměr stran (volný, 1:1, 4:3, 3:2, 16:9),
- **nahrazení** souborem JPEG, PNG, GIF nebo WebP do 10 MB.

Obrázek se ukládá do AbraFlexi jako příloha ceníkové položky s poznámkou `image`, z něj se vyrobí
náhled `thumbnail` (nejdéle 150 px) – stejně je čte ClientZone. Nová příloha se přidá dřív, než se
odstraní stará, takže při chybě zůstane původní obrázek. Úpravy (otočení, ořez) dělá PHP rozšíření
GD v `AdminZone\ImageEditor`; SVG se neupravuje. Endpoint `productimage.php` vyžaduje přihlášení
a CSRF token. Nová, ještě neuložená položka obrázek mít nemůže.

## Karta klienta

Stránka `clientcard.php?id=<id adresy nebo code:KÓD>` (odkaz ze seznamu klientů, z adresy je tlačítko
*Karta klienta*) shrnuje vše, co AbraFlexi ví o vztahu s jedním zákazníkem. Je jen pro čtení;
úpravy adresy vedou na `adresar.php`.

- **upozornění** – faktury po splatnosti (kolik, na kolik a o kolik dní), smlouvy končící do 60 dnů,
  chybějící e-mail pro faktury a upomínky, jinak zelené „vše zaplaceno“
- **čtyři čísla** – neuhrazeno, po splatnosti, fakturováno za 12 měsíců, aktivní smlouvy
- **klient a kontakty** – IČO, DIČ, adresa, e-mail, telefon, štítky; kontakty (`kontakt`)
- **služby** – stav párování IPEX, Subreg a SledovaniTV s odkazem do účtu u služby
- **faktury a smlouvy** – posledních 10 vydaných faktur (po splatnosti zvýrazněné) a smlouvy
  (`smlouva`) aktivní, brzy končící a ukončené (včetně vypovězených)

Částky se počítají v Kč (`zbyvaUhradit`, `sumCelkem`); cizí měny se nepřepočítávají. Data se čtou
z evidencí `adresar`, `kontakt`, `faktura-vydana`, `smlouva` a `adresar-bankovni-ucet`; sestavení
karty (`AdminZone\ClientCard::build()`) nepotřebuje spojení s AbraFlexi a je pokryto testy.

## Licence

MIT, viz `LICENSE`. Přibalené knihovny v `src/assets` a `src/js` mají vlastní licence.

## Struktura

- `src/extids.php` – přiřazení účtů IPEX / Subreg / SledovaniTV k zákazníkům AbraFlexi (EXT:id)
- `src/AdminZone/` – třídy (`AdminZone\`): `ExtIdService` a služby, `User` (přihlášení tokenem AbraFlexi)
- `src/clients.php`, `src/adresar.php` – seznam klientů a úprava adresy
- `src/clientcard.php` – karta klienta (`ClientCard`, `ui\ClientCardView`)
- `src/pricelist.php` – kontrola ceníku (`PricelistChecker`, `ui\PricelistReport`)
- `src/pricelisttree.php`, `src/pricelistitem.php` – strom a editor ceníku (`PricelistTree`, `PricelistItem`)
- `src/productimage.php` – obrázky produktů (`ProductImages`, `ImageEditor`, `ui\ProductImageEditor`, `src/js/productimage.js`)
- `tests/` – PHPUnit (`vendor/bin/phpunit`)
- `i18n/` – gettext doména `adminzone`
- `debian/` – balíčkování

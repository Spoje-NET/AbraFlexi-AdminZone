# AbraFlexi AdminZone

**English** | [Česky](README.cs.md)

Administration portal for [AbraFlexi](https://www.abraflexi.eu/) operators – the counterpart of
[AbraFlexi-ClientZone](https://github.com/VitexSoftware/AbraFlexi-ClientZone): customer management
and pairing of customer accounts with the IPEX, Subreg and SledovaniTV services (EXT:id). Payment
matching, reminders, webhook processing and digests are separate projects (`abraflexi-matcher`,
`abraflexi-reminder`, `abraflexi-webhook-acceptor`, `DigestModules`).

It was split off the administration part of the `system.spoje.net` project.

## Features

- **Clients** – client list, editing of the address and contacts, the **client card** (see below)
- **Services** – pairing of IPEX, Subreg and SledovaniTV accounts with AbraFlexi customers, registration of a contact at Subreg and SledovaniTV
- **Pricelist and the offer for ClientZone** – pricelist check, tree and item editor, **product pictures** (turn, crop, replace)
- **Search** across AbraFlexi evidences

## Installation

    composer install
    cp .env.example .env     # fill in the credentials
    php -S localhost:8080 -t src

Sign-in: `login.php` – the credentials and the token are verified by AbraFlexi (`/login-logout/login`).
The application uses no database of its own.

Debian package: `abraflexi-adminzone` (`debuild -i -us -uc -b`), configuration in `/etc/abraflexi-admin-zone/.env`.

Tests: `vendor/bin/phpunit`.

## Pricelist check

The page `pricelist.php` (menu *Pricelist*) checks that [ClientZone](https://github.com/VitexSoftware/AbraFlexi-ClientZone)
can sell the pricelist items correctly. ClientZone offers the items labelled `ESHOP` and `AKTIVNI`;
an ordered item becomes an order (stock type *service*) or an advance invoice (*goods*, *material*,
*fee*); other types are refused.

| Severity | Check | Meaning |
|---|---|---|
| error | `unsupported-type` | the offered item has a stock type ClientZone does not process |
| error | `no-name`, `negative-price` | missing name, negative price |
| error | `expired`, `not-yet-valid` | the offered item is valid only until/from another year (`platiDo`, `platiOd`) |
| error | `credit-unit-price` | a credit (`KREDIT_DOMENA`) has a unit price other than 1 |
| error | `missing-product`, `product-not-offered` | a product the shop depends on is missing in the pricelist or customers cannot see it |
| warning | `zero-price`, `no-vat`, `no-unit` | the offered item is free, has no VAT rate or no unit (for a service the unit gives the billing period) |
| warning | `duplicate-code`, `code-spaces` | the same code on more items (regardless of case and spaces), spaces around the code |
| notice | `half-labelled`, `no-description`, `code-with-space` | only one of the labels, no description, a space in the code |

Items without the labels `ESHOP` and `AKTIVNI` are judged only for data errors (name, negative
price, duplicates); the shop rules do not apply to them. The report can be filtered by severity.

The products the shop must have are set in `.env` (optional; when not set, their existence is not checked):

    PRICELIST_EXPECTED_PRODUCTS="MULTIFLEXI,HOSTING_WEB,HOSTING_MAIL,KREDIT_DOMENA,KREDIT_VOIP"

The codes are used by the order plugins of ClientZone. `platiOd` and `platiDo` are taken as years;
the values `0` and `9999` mean no limit.

### Pricelist editor and tree

The page **Pricelist → Tree and items** (`pricelisttree.php`) shows the pricelist tree of AbraFlexi
(evidence `strom`, root `STR_CEN`) with item counts and, for the selected branch, a table of items:
stock type, labels and whether ClientZone customers see them. The **editor** (`pricelistitem.php`)
offers choices instead of typing codes by hand:

- **stock type** – ClientZone sells a service (a received order is created) and goods, material or a
  fee (an advance invoice is created); the editor refuses other types for an item that is offered,
- **labels** from the list of pricelist labels, the steering `ESHOP` and `AKTIVNI` first,
- **place in the tree** (`strom-cenik`) – ClientZone offers the items below its catalog branch,
- unit of measure, goods group, VAT rate and validity.

The catalog branch of ClientZone (its setting `PROD_CAT`) is set in the `.env` of AdminZone too, so
the editor can tell the items that are not offered (when not set, the whole tree is taken):

    PROD_CAT=1

### Product pictures

A stored item (`pricelistitem.php?id=…`) has the panel **Picture**:

- **turn** left, right and by 180°,
- **crop** – drag over the picture to select the area; the aspect ratio can be chosen (free, 1:1, 4:3, 3:2, 16:9),
- **replace** with a JPEG, PNG, GIF or WebP file up to 10 MB.

The picture is stored in AbraFlexi as an attachment of the pricelist item with the note `image`, and
the thumbnail `thumbnail` (at most 150 px) is made from it – ClientZone reads them the same way.
The new attachment is added before the old one is removed, so a failure leaves the original picture.
Turning and cropping is done by the PHP GD extension in `AdminZone\ImageEditor`; SVG is not edited.
The endpoint `productimage.php` requires sign-in and a CSRF token. A new item that is not stored yet
cannot have a picture.

## Client card

The page `clientcard.php?id=<address id or code:CODE>` (linked from the client list; the address page
has the button *Client card*) sums up all AbraFlexi knows about the relation with one customer. It is
read-only; editing the address leads to `adresar.php`.

- **warnings** – overdue invoices (how many, how much and by how many days), contracts ending within
  60 days, a missing e-mail for invoices and reminders, otherwise a green "everything is paid"
- **four numbers** – unpaid, overdue, invoiced in 12 months, active contracts
- **client and contacts** – company ID, VAT ID, address, e-mail, phone, labels; contacts (`kontakt`)
- **services** – pairing state of IPEX, Subreg and SledovaniTV with a link to the account at the service
- **invoices and contracts** – the latest 10 issued invoices (overdue ones highlighted) and contracts
  (`smlouva`) active, ending soon and ended (including terminated ones)

Amounts are counted in CZK (`zbyvaUhradit`, `sumCelkem`); foreign currencies are not converted. The
data is read from the evidences `adresar`, `kontakt`, `faktura-vydana`, `smlouva` and
`adresar-bankovni-ucet`; putting the card together (`AdminZone\ClientCard::build()`) needs no
connection to AbraFlexi and is covered by tests.

## License

MIT, see `LICENSE`. The bundled libraries in `src/assets` and `src/js` have their own licenses.

## Structure

- `src/extids.php` – pairing of IPEX / Subreg / SledovaniTV accounts with AbraFlexi customers (EXT:id)
- `src/AdminZone/` – classes (`AdminZone\`): `ExtIdService` and the services, `User` (sign-in by an AbraFlexi token)
- `src/clients.php`, `src/adresar.php` – client list and address editing
- `src/clientcard.php` – client card (`ClientCard`, `ui\ClientCardView`)
- `src/pricelist.php` – pricelist check (`PricelistChecker`, `ui\PricelistReport`)
- `src/pricelisttree.php`, `src/pricelistitem.php` – pricelist tree and editor (`PricelistTree`, `PricelistItem`)
- `src/productimage.php` – product pictures (`ProductImages`, `ImageEditor`, `ui\ProductImageEditor`, `src/js/productimage.js`)
- `tests/` – PHPUnit (`vendor/bin/phpunit`)
- `i18n/` – gettext domain `adminzone`
- `debian/` – packaging

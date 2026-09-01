# Server sync — 1 account, overal dezelfde data

Dit lost op dat je journal-data nu alleen in de browser (localStorage) van
één toestel staat. Met deze kleine PHP-server bewaar je alles op je eigen
gratis PHP-host; zodra je ergens anders met dezelfde gebruikersnaam +
wachtwoord inlogt, krijg je automatisch dezelfde data te zien.

```
Browser (elk toestel/elke browser)
      ↓  inloggen met gebruikersnaam + wachtwoord
      ↓  GET  load.php   -> haalt opgeslagen data op
      ↓  POST save.php   -> slaat elke wijziging op
server-sync/php  (jij host dit, net als mt5-bridge/php)
```

## 1. Uploaden

Zelfde aanpak als bij `mt5-bridge/php/` (zie die README voor uitleg over
Bestandsbeheer/FTP als je dat nog niet gedaan hebt):

1. Maak op je host een map, bv. `server-sync`.
2. Upload `config.php`, `save.php`, `load.php` en de `data/`-map (met daarin
   `.htaccess`) uit deze map ernaartoe.
3. Zorg dat de `data/`-map beschrijfbaar is (rechten `755`/`775`) — daar
   komt straks één bestand per account in te staan (bv. `Test.json`).
4. De `.htaccess` in `data/` blokkeert direct browsen naar die map, zodat
   niemand je opgeslagen JSON-bestanden rechtstreeks kan openen.

Na uploaden zijn de twee endpoints bereikbaar op:

```
https://jouwdomein.../server-sync/load.php
https://jouwdomein.../server-sync/save.php
```

## 2. Account/wachtwoord instellen

`config.php` bevat het standaardaccount `Test` / `Test` (wachtwoord staat
er **niet** in leesbare vorm — er staat een hash in, gegenereerd met
`password_hash()`). Wil je een ander wachtwoord? Genereer een nieuwe hash:

```bash
php -r "echo password_hash('jouw-nieuwe-wachtwoord', PASSWORD_DEFAULT);"
```

en vervang de waarde achter `'Test' =>` in `config.php` door die nieuwe hash
(upload het bestand daarna opnieuw).

## 3. Koppelen in de Trade Journal

Op het inlogscherm vul je, naast gebruikersnaam en wachtwoord, ook de
**Server URL** in — dat is de map-URL hierboven zonder `/load.php` erachter,
dus bijvoorbeeld:

```
https://jouwdomein.../server-sync
```

Die URL wordt onthouden in de browser (zodat je hem niet steeds opnieuw
hoeft in te typen op hetzelfde toestel), en wordt gebruikt om bij het
inloggen je data op te halen en bij elke wijziging automatisch op te slaan.

Log je op een ander toestel/browser in met dezelfde gebruikersnaam,
wachtwoord én server-URL, dan krijg je precies dezelfde data te zien.

## Let op

- Dit blijft, net als de inlogpagina zelf, **geen zware beveiliging** — het
  wachtwoord wordt over een gewone HTTP(S)-verbinding verstuurd. Gebruik
  altijd een host met HTTPS, en behandel dit niet als een plek voor
  gevoelige financiële gegevens die je écht wil beschermen.
- Zonder ingevulde Server URL werkt de journal gewoon zoals voorheen,
  alleen lokaal in die ene browser (localStorage) — niets breekt als je
  deze stap overslaat.

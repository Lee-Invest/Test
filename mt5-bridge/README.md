# MT5 → EA/bridge → API → Trade Journal

Deze map bevat de twee stukken die je zelf moet draaien om MetaTrader 5
automatisch te koppelen aan de Trade Journal webpagina:

```
MetaTrader 5
      ↓  (EA, lokaal in MT5)
TradeJournalBridge.mq5
      ↓  (HTTP POST /ingest)
bridge_server.py   <-- jij host dit ergens 24/7
      ↑  (HTTP GET /trades)
Trade Journal (de webpagina, "MT5 koppelen" knop)
```

Er komen **geen MT5-inloggegevens** in de browser terecht. De EA praat
namens jouw ingelogde MT5-terminal met de bridge; de journal-pagina haalt
alleen klaar-verwerkte trades op.

## 1. Bridge-server draaien

```bash
pip install flask flask-cors
python bridge_server.py
```

Dit start een server op poort 5000 met twee routes:

- `POST /ingest` — hier stuurt de EA elke gesloten trade naartoe.
- `GET /trades?account=<accountKey>` — hier leest de Trade Journal pagina trades uit.

Voor gebruik met de echte website moet deze server ergens **publiek bereikbaar**
draaien (niet alleen op je eigen laptop), bijvoorbeeld:

- Een goedkope VPS (DigitalOcean, Hetzner, etc.) — `python bridge_server.py` erop draaien, eventueel achter nginx + HTTPS.
- Render.com / Railway.app — gratis/goedkope hosting die een Flask-app direct kan draaien.
- Tijdelijk testen: `ngrok http 5000` geeft je een publieke HTTPS-URL die naar je lokale server wijst.

Optioneel: zet een geheime sleutel om `/ingest` te beveiligen:

```bash
export BRIDGE_SECRET="iets-geheims"
python bridge_server.py
```

Vul dan dezelfde waarde in bij `BridgeSecret` in de EA.

## 2. EA installeren in MetaTrader 5

1. Kopieer `TradeJournalBridge.mq5` naar `MQL5/Experts/` in je MT5 data-map
   (in MT5: **Bestand → Datamap openen**).
2. Open MetaEditor, compileer het bestand (F7).
3. In MT5: **Tools → Opties → Expert Advisors** → vink *"Allow WebRequest for
   listed URL"* aan en voeg het domein van je bridge-server toe (bv.
   `https://jouw-bridge.nl`).
4. Sleep de EA op een grafiek van het account dat je wilt koppelen.
5. Vul in de EA-instellingen in:
   - `BridgeUrl` — het volledige `/ingest`-adres van je bridge-server.
   - `AccountKey` — een zelfgekozen naam voor dit account (bv. `main-ftmo-200k`).
     Deze moet **exact** hetzelfde zijn als wat je straks in de Trade Journal invult.
   - `BridgeSecret` — alleen invullen als je die bij de server hebt gezet.

Vanaf nu stuurt de EA automatisch elke gesloten trade naar je bridge-server.

## 3. Koppelen in de Trade Journal

Klik op **"MT5 koppelen"** naast "+ trade" bij het account-tabblad, en vul in:

- **API URL** — het `/trades`-adres van je bridge-server (bv. `https://jouw-bridge.nl/trades`).
- **Account key** — exact dezelfde waarde als `AccountKey` in de EA.
- **Automatisch synchroniseren** — kies een interval, of laat op "Uit" voor
  alleen handmatig synchroniseren via de knop.

Klik op **"Opslaan & synchroniseren"**. De trades die de EA tot nu toe heeft
doorgestuurd verschijnen direct in de tabel; bij een gekozen interval haalt
de pagina daarna zelf steeds opnieuw op zolang de pagina open staat.

## Uitbreiden

`bridge_server.py` slaat trades nu simpel op in `trades.json`. Voor
serieuzer gebruik kun je dit vervangen door een echte database (SQLite/
Postgres) zonder dat de EA of de Trade Journal pagina hoeven te veranderen —
zolang `/ingest` en `/trades` hetzelfde contract blijven volgen.

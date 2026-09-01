"""
Bridge server tussen de MetaTrader 5 EA en de Trade Journal webpagina.

Architectuur:
    MetaTrader 5 -> EA (TradeJournalBridge.mq5) -> POST /ingest  -> deze server
    Trade Journal (browser)                     -> GET  /trades -> deze server

Draai dit ergens waar het 24/7 bereikbaar is (VPS, Render, Railway, etc.)
met een publiek adres, bv. https://jouw-bridge.nl

Installeren:
    pip install flask flask-cors

Starten:
    python bridge_server.py

Trades worden simpel weggeschreven naar trades.json (per account key).
Voor productiegebruik kun je dit later vervangen door een echte database.
"""

import json
import os
from datetime import datetime

from flask import Flask, request, jsonify
from flask_cors import CORS

app = Flask(__name__)
CORS(app)  # staat de Trade Journal (elke origin) toe om GET /trades te doen

DATA_FILE = os.path.join(os.path.dirname(__file__), "trades.json")

# Optioneel: zet dit gelijk aan BridgeSecret in de EA om ingest te beveiligen.
BRIDGE_SECRET = os.environ.get("BRIDGE_SECRET", "")


def load_data():
    if not os.path.exists(DATA_FILE):
        return {}
    with open(DATA_FILE, "r", encoding="utf-8") as f:
        try:
            return json.load(f)
        except json.JSONDecodeError:
            return {}


def save_data(data):
    with open(DATA_FILE, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, ensure_ascii=False)


@app.route("/ingest", methods=["POST"])
def ingest():
    """Ontvangt een enkele trade van de MT5 EA."""
    if BRIDGE_SECRET:
        if request.headers.get("X-Bridge-Secret", "") != BRIDGE_SECRET:
            return jsonify({"error": "unauthorized"}), 401

    payload = request.get_json(silent=True)
    if not payload or "account" not in payload:
        return jsonify({"error": "invalid payload, 'account' verplicht"}), 400

    account = str(payload["account"])
    trade = {
        "ticket": payload.get("ticket"),
        "datum": payload.get("datum", ""),
        "asset": payload.get("asset", ""),
        "resultaat": payload.get("resultaat", 0),
        "rr": payload.get("rr", "3"),
        "notities": payload.get("notities", ""),
        "receivedAt": datetime.utcnow().isoformat(),
    }

    data = load_data()
    data.setdefault(account, [])

    # Voorkom duplicaten als de EA hetzelfde ticket twee keer stuurt.
    ticket = trade["ticket"]
    if ticket is not None:
        data[account] = [t for t in data[account] if t.get("ticket") != ticket]

    data[account].append(trade)
    save_data(data)

    return jsonify({"ok": True, "count": len(data[account])})


@app.route("/trades", methods=["GET"])
def trades():
    """Wordt aangeroepen door de Trade Journal pagina om trades op te halen."""
    account = request.args.get("account", "")
    data = load_data()
    account_trades = data.get(account, [])

    # Exact het formaat dat de Trade Journal verwacht: een platte lijst.
    result = [
        {
            "datum": t.get("datum", ""),
            "asset": t.get("asset", ""),
            "resultaat": t.get("resultaat", 0),
            "rr": t.get("rr", "3"),
            "notities": t.get("notities", ""),
        }
        for t in account_trades
    ]
    return jsonify(result)


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)

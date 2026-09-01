//+------------------------------------------------------------------+
//| TradeJournalBridge.mq5                                          |
//| Expert Advisor: stuurt gesloten trades naar de bridge-server     |
//| die de Trade Journal webpagina uitleest.                         |
//|                                                                    |
//| Architectuur:                                                     |
//|   MetaTrader 5 -> deze EA -> bridge-server (POST /ingest)         |
//|   Trade Journal (browser) -> bridge-server (GET /trades) -> tabel |
//+------------------------------------------------------------------+
#property strict
#property version   "1.00"

// --- Instellingen: pas deze aan per account/tabblad ---
input string BridgeUrl    = "https://jouw-bridge.nl/ingest"; // moet exact overeenkomen met je server
input string AccountKey   = "main-ftmo-200k";                 // moet overeenkomen met het "Account key" veld in de Trade Journal
input string BridgeSecret = "";                                 // optioneel: gedeeld geheim, wordt als header meegestuurd

//+------------------------------------------------------------------+
//| BELANGRIJK: voordat WebRequest werkt moet je in MT5 de URL       |
//| toevoegen aan Tools > Opties > Expert Advisors >                 |
//| "Allow WebRequest for listed URL" (het domein van BridgeUrl).    |
//+------------------------------------------------------------------+

int OnInit()
{
   return(INIT_SUCCEEDED);
}

// Wordt aangeroepen bij elke transactie in de account-historie.
// We reageren alleen op DEAL_ENTRY_OUT (een positie die sluit = een afgeronde trade).
void OnTradeTransaction(const MqlTradeTransaction& trans,
                         const MqlTradeRequest&     request,
                         const MqlTradeResult&      result)
{
   if(trans.type != TRADE_TRANSACTION_DEAL_ADD)
      return;

   ulong dealTicket = trans.deal;
   if(dealTicket == 0) return;

   if(!HistoryDealSelect(dealTicket)) return;

   long entry = HistoryDealGetInteger(dealTicket, DEAL_ENTRY);
   if(entry != DEAL_ENTRY_OUT) return; // alleen sluitende deals = afgeronde trade

   string symbol   = HistoryDealGetString(dealTicket, DEAL_SYMBOL);
   double profit   = HistoryDealGetDouble(dealTicket, DEAL_PROFIT)
                    + HistoryDealGetDouble(dealTicket, DEAL_SWAP)
                    + HistoryDealGetDouble(dealTicket, DEAL_COMMISSION);
   double volume   = HistoryDealGetDouble(dealTicket, DEAL_VOLUME);
   datetime closeTime = (datetime)HistoryDealGetInteger(dealTicket, DEAL_TIME);
   string comment  = HistoryDealGetString(dealTicket, DEAL_COMMENT);

   SendTradeToBridge(dealTicket, symbol, profit, volume, closeTime, comment);
}

void SendTradeToBridge(ulong ticket, string symbol, double profit, double volume,
                        datetime closeTime, string comment)
{
   string dateStr = TimeToString(closeTime, TIME_DATE);
   StringReplace(dateStr, ".", "-");

   string json = StringFormat(
      "{\"account\":\"%s\",\"ticket\":%I64u,\"datum\":\"%s\",\"asset\":\"%s\",\"resultaat\":%.2f,\"volume\":%.2f,\"notities\":\"%s\"}",
      AccountKey, ticket, dateStr, symbol, profit, volume, comment
   );

   char postData[];
   StringToCharArray(json, postData, 0, StringLen(json));

   string headers = "Content-Type: application/json\r\n";
   if(BridgeSecret != "")
      headers += "X-Bridge-Secret: " + BridgeSecret + "\r\n";

   char result[];
   string resultHeaders;
   int timeout = 5000;

   int status = WebRequest("POST", BridgeUrl, headers, timeout, postData, result, resultHeaders);

   if(status == -1)
   {
      Print("TradeJournalBridge: WebRequest fout ", GetLastError(),
            " - controleer of ", BridgeUrl, " is toegestaan bij Expert Advisors > WebRequest URLs.");
   }
   else if(status >= 400)
   {
      Print("TradeJournalBridge: bridge-server gaf status ", status, ": ", CharArrayToString(result));
   }
   else
   {
      Print("TradeJournalBridge: trade ", ticket, " (", symbol, ", ", profit, ") verstuurd.");
   }
}

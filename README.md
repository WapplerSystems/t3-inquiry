# TYPO3 Extension `inquiry`

Die TYPO3-Extension `inquiry` ist eine universelle und hochflexible Lösung, um Besuchern Ihrer Website das Sammeln von Produkten oder Leistungen in einer Anfrageliste zu ermöglichen. Ähnlich wie bei einem Warenkorb können Nutzer Artikel hinzufügen, verwalten und anschließend ein individuelles Angebot anfordern – oder die Liste als **PDF exportieren** und per **Preload-Link** erneut aufrufen.

[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg?style=flat-square)](https://typo3.org/)
[![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

## 🚀 Vorteile & Funktionen

- **Universell einsetzbar**: Dank eines Adapter-Konzepts kann die Extension mit beliebigen Datensätzen (Produkte, Seiten, News, etc.) arbeiten.
- **Moderne User Experience**:
    - Produkte können ohne Neuladen der Seite per **Ajax** hinzugefügt oder entfernt werden.
    - Dynamische Aktualisierung der Badge-Counter (z. B. im Header).
- **Flexibles Formularwesen**: Nutzt das TYPO3 Form-Framework. Formulare können einfach per Event-Listener erweitert oder angepasst werden.
- **Zwei-Mail-Versand**: Beim Absenden der Anfrageliste erhalten sowohl die konfigurierten Empfänger (`EmailToReceiver`) als auch der Anfragende selbst eine Bestätigungsmail (`EmailToSender`). Beide Mails sind über eigene Events anpassbar.
- **FlyIn-Liste**: Optionaler Bootstrap-Offcanvas, der die aktuelle Anfrageliste (Bild + Titel + Entfernen-Button) als ausfahrbares Panel anzeigt. Per Custom-Event (`inquiry:item-removed`) synchronisiert sich die FlyIn live mit der Anfrageliste-Seite und umgekehrt.
- **PDF-Export**: Die Anfrageliste kann inklusive ausgefüllter Felder als PDF heruntergeladen werden.
- **Preload-Link**: Jedes PDF enthält einen eindeutigen Link, mit dem die Liste auf der Website wiederhergestellt und die Felder vorausgefüllt werden können.
- **Einfache Integration**: ViewHelper für Buttons ("In die Anfrageliste") und Links zur Liste werden mitgeliefert.
- **Entwicklerfreundlich**: Umfangreiche PSR-14 Events ermöglichen tiefgreifende Anpassungen im Prozess (z. B. eigene Finisher, Validierungen oder Datenauflösung).
- **Zukunftssicher**: Volle Unterstützung für TYPO3 v13.

## 🛠 Funktionsweise

Die Extension verwaltet eine Liste von Identifikatoren (z. B. UIDs). Über einen **Adapter** (Event-Listener) entscheiden Sie, wie diese IDs in reale Objekte aufgelöst werden und welche Informationen im Anfrageformular erscheinen sollen.

Listen-Zustände (Items + Formularfeld-Vorausfüllwerte) werden als **DB-Snapshots** in der Tabelle `tx_inquiry_list_snapshot` gespeichert. Jeder Snapshot erhält einen deterministischen 32-stelligen Bezeichner (MD5 der serialisierten Daten), der als URL-Parameter weitergegeben wird. URL-Parameter zur direkten Datenübertragung werden nicht mehr verwendet.

Ein Beispiel für einen Adapter finden Sie im Verzeichnis der Extension oder als separates Repository.

## 📥 Installation

Die empfohlene Installation erfolgt über [Composer](https://getcomposer.org/):

```bash
composer require wapplersystems/inquiry
```

Nach der Installation muss das Datenbankschema aktualisiert werden:

```bash
vendor/bin/typo3 database:updateschema
```

## 📋 Einrichtung

1.  **TypoScript einbinden**: Fügen Sie das statische TypoScript der Extension zu Ihrem Template hinzu.
2.  **Adapter erstellen**: Implementieren Sie Event-Listener für `ResolveItemEvent`, um Ihre Objekte (z. B. Produkte) der Extension bekannt zu machen.
3.  **ViewHelper nutzen**: Integrieren Sie die Buttons und die FlyIn in Ihre Fluid-Templates:
    ```html
    {namespace i=WapplerSystems\Inquiry\ViewHelpers}
    <i:button.toggleItem item="{product}" />
    <i:button.flyIn />            <!-- Trigger im Header -->
    <i:flyIn.panel />              <!-- Offcanvas einmal pro Seite (z. B. vor </body>) -->
    ```
4.  **Plugins einbinden**: Für PDF-Export und Preload werden zusätzliche Plugins/typeNums benötigt (siehe unten).

## 🔢 typeNums & Endpunkte

| typeNum | Action | Zweck |
|---------|--------|-------|
| 678934 | `toggleItemStatus` | Artikel hinzufügen / entfernen |
| 678935 | `getItems` | Aktuelle Liste zurückgeben (`{items}`) |
| 678936 | `removeItem` | Artikel entfernen |
| 678937 | `preloadItems` | Snapshot aus DB laden, Session befüllen, Weiterleitung mit Identifier |
| 678938 | `generatePdf` | Snapshot aus DB laden, PDF rendern und ausliefern |
| 678939 | `saveListSnapshot` | POST-Endpunkt – Items + Prefill speichern, gibt `{identifier}` zurück |
| 678940 | `getPrefill` | GET – Prefill-Daten für einen Identifier zurückgeben |
| 678941 | `flyInItems` | HTML-Fragment der aktuellen Anfrageliste für die FlyIn |

Die URLs für typeNum 678939, 678940 und 678941 werden automatisch als Meta-Tags in alle Seiten eingefügt:
- `<meta name="inquiry-save-snapshot" ...>` → URL für typeNum 678939
- `<meta name="inquiry-get-prefill" ...>` → URL für typeNum 678940
- `<meta name="inquiry-flyin-items" ...>` → URL für typeNum 678941

## 📄 PDF-Export

### Ablauf

1. Nutzer klickt auf `.inquiry-generate-pdf`.
2. JavaScript sammelt alle Eingaben mit `[data-inquiry-pdf-key][data-inquiry-pdf-hash]` als Prefill-Objekt.
3. JavaScript sendet `{items, prefill}` per POST-JSON an `saveListSnapshotAction` (typeNum 678939).
4. Server speichert den Snapshot in der DB und gibt `{identifier}` zurück.
5. JavaScript navigiert zu `?type=678938&tx_inquiry[identifier]=<identifier>`.
6. `generatePdfAction` lädt den Snapshot, rendert das PDF und liefert es aus.
7. Das PDF enthält einen Preload-Link: `/?type=678937&tx_inquiry[identifier]=<identifier>`.

### Adapter-Integration

Im Adapter-Event-Listener werden die Formularfelder, die im PDF erscheinen sollen, mit `data-inquiry-pdf-key` und `data-inquiry-pdf-hash` ausgezeichnet (z. B. in `buildFormItem()`). `resolveItem()` liest `$event->getPdfFields()` und rendert ein dediziertes PDF-Template (z. B. `Item/ItemPdf.html`), das als `htmlPreviewPdf` am Event gesetzt wird.

## 🔗 Preload-Link

### Ablauf

1. Nutzer klickt den Link im PDF.
2. `preloadItemsAction` (typeNum 678937) liest den Identifier, lädt die Items aus dem DB-Snapshot und schreibt sie in die Session.
3. Weiterleitung zur Listenseite mit `?tx_inquiry[identifier]=<identifier>`.
4. JavaScript erkennt den Identifier in der URL, ruft `getPrefillAction` (typeNum 678940) auf und befüllt die Formularfelder mit den zurückgegebenen Werten.

## 🗄 Datenbankschema

Tabelle `tx_inquiry_list_snapshot`:

| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `identifier` | `CHAR(32)` PRIMARY KEY | MD5 von `json_encode(['items' => ..., 'prefill' => ...])` |
| `items` | `TEXT` (JSON) | Array von `{uid, type, hash}` |
| `prefill` | `TEXT` (JSON) | `{hash: {key: value}}` – Feldwerte für das PDF |
| `crdate` | `INT` | Erstellungszeitstempel |

Der Identifier ist deterministisch: gleiche Items + gleicher Prefill erzeugen immer denselben Hash, sodass doppelte DB-Einträge automatisch vermieden werden.

## 🏗 Events

Nutzen Sie die folgenden Events für individuelle Anpassungen:

- `BuildInquiryFormEvent`: Passt die Formular-Definition an.
- `BuildInquiryFormContactEvent`: Ergänzt Kontaktfelder im Formular.
- `BuildInquiryFormItemEvent`: Ergänzt item-spezifische Felder pro Artikel.
- `ResolveItemEvent`: Löst eine UID in ein Objekt auf; setzt `htmlPreview` (Web) und `htmlPreviewPdf` (PDF).
- `CanResolveItemByIdentifierEvent`: Prüft, ob der Adapter für einen gegebenen Typ zuständig ist.
- `CreateEmailToReceiverFinisherEvent`: Ermöglicht die Anpassung der Empfänger-Mail (Vorlage, Betreff, Reply-To etc.).
- `CreateEmailToSenderFinisherEvent`: Ermöglicht die Anpassung der Bestätigungs-Mail an den Anfragenden.
- ... und viele weitere.

## 📧 E-Mail-Versand

Beim Absenden der Anfrageliste werden zwei Mails verschickt:

1. **An die konfigurierten Empfänger** (`EmailToReceiver`): enthält die Kontaktdaten des Anfragenden und die Liste der angefragten Artikel.
2. **An den Anfragenden** (`EmailToSender`): Bestätigungsmail mit derselben Zusammenfassung; das Reply-To ist auf die Standard-Absender-Adresse des Systems gesetzt, damit Antworten an die Empfänger gehen.

Beide Mails verwenden das TYPO3 Form-Framework `EmailFinisher`. Adapter können über die jeweiligen `Create*FinisherEvent`-Events Vorlagen, Layouts, Betreff oder Empfänger überschreiben.

## 🛒 FlyIn (Offcanvas-Liste)

Die FlyIn ist ein optionaler Bootstrap-Offcanvas, mit dem Nutzer ihre aktuelle Anfrageliste jederzeit aus dem Header heraus einsehen können – inklusive Vorschaubild, Titel und Entfernen-Button pro Artikel sowie eines CTA-Links zur vollständigen Anfrageliste-Seite (`plugin.tx_inquiry.settings.listPageUid`).

### Ablauf

1. `<i:button.flyIn />` im Header öffnet das Offcanvas (Bootstrap `data-bs-toggle="offcanvas"`).
2. Beim Öffnen lädt `inquiry-flyin.js` per `fetch` ein HTML-Fragment vom `flyInItemsAction` (typeNum 678941) und ersetzt den Inhalt der Offcanvas-Body.
3. Die Standard-Vorlage `Templates/Inquiry/FlyInItems.html` rendert Bild + Titel + Entfernen-Button (Adapter können eine eigene Variante hinterlegen, um z. B. Spec-Lines anzuzeigen).
4. Bei leerer Liste wird ein Empty-State angezeigt und der CTA ausgeblendet.

### Cross-Component-Sync

Beide Entfern-Pfade (Button in der FlyIn, Delete-Button in der Anfrageliste-Seite) feuern nach erfolgreichem Remove-Request ein Custom-Event:
```javascript
document.dispatchEvent(new CustomEvent('inquiry:item-removed', {
    detail: { uid, type }
}));
```
Beide Komponenten lauschen darauf und entfernen das passende DOM-Element optimistisch (clientseitig), ohne erneuten Server-Roundtrip. Lediglich beim Wechsel auf den Empty-State wird der Inhalt der FlyIn frisch geladen.

### Adapter-Integration

Adapter überschreiben üblicherweise:
- `Templates/Inquiry/FlyInItems.html` für reichere Item-Darstellung (z. B. Spec-Lines).
- `resolveItem()`-Listener: `$event->setResolvedImage(...)` setzen, damit Vorschaubilder erscheinen.

---

## Authors

* [Sven Wappler](https://github.com/svewap) - [wappler.systems](https://wappler.systems)
* [Ilja Melnicenko](https://github.com/ille216) - [wappler.systems](https://wappler.systems)

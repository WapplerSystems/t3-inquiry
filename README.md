# TYPO3 Extension `inquiry`

Die TYPO3-Extension `inquiry` ist eine universelle und hochflexible Lösung, um Besuchern Ihrer Website das Sammeln von Produkten oder Leistungen in einer Anfrageliste zu ermöglichen. Ähnlich wie bei einem Warenkorb können Nutzer Artikel hinzufügen, verwalten und anschließend ein individuelles Angebot anfordern.

[![TYPO3 13](https://img.shields.io/badge/TYPO3-13-orange.svg?style=flat-square)](https://typo3.org/)
[![License](https://img.shields.io/badge/license-GPL--2.0-blue.svg?style=flat-square)](https://www.gnu.org/licenses/gpl-2.0.html)

## 🚀 Vorteile & Funktionen

- **Universell einsetzbar**: Dank eines Adapter-Konzepts kann die Extension mit beliebigen Datensätzen (Produkte, Seiten, News, etc.) arbeiten.
- **Moderne User Experience**:
    - Produkte können ohne Neuladen der Seite per **Ajax** hinzugefügt oder entfernt werden.
    - Dynamische Aktualisierung der Badge-Counter (z. B. im Header).
- **Flexibles Formularwesen**: Nutzt das TYPO3 Form-Framework. Formulare können einfach per Event-Listener erweitert oder angepasst werden.
- **Einfache Integration**: ViewHelper für Buttons ("In die Anfrageliste") und Links zur Liste werden mitgeliefert.
- **Entwicklerfreundlich**: Umfangreiche PSR-14 Events ermöglichen tiefgreifende Anpassungen im Prozess (z. B. eigene Finisher, Validierungen oder Datenauflösung).
- **Zukunftssicher**: Volle Unterstützung für TYPO3 v13.

## 🛠 Funktionsweise

Die Extension verwaltet eine Liste von Identifikatoren (z. B. UIDs). Über einen **Adapter** (Event-Listener) entscheiden Sie, wie diese IDs in reale Objekte aufgelöst werden und welche Informationen im Anfrageformular erscheinen sollen.

Ein Beispiel für einen Adapter finden Sie im Verzeichnis der Extension oder als separates Repository.

## 📥 Installation

Die empfohlene Installation erfolgt über [Composer](https://getcomposer.org/):

```bash
composer require wapplersystems/inquiry
```

## 📋 Einrichtung

1.  **TypoScript einbinden**: Fügen Sie das statische TypoScript der Extension zu Ihrem Template hinzu.
2.  **Adapter erstellen**: Implementieren Sie Event-Listener für `ResolveItemEvent`, um Ihre Objekte (z. B. Produkte) der Extension bekannt zu machen.
3.  **ViewHelper nutzen**: Integrieren Sie die Buttons in Ihre Fluid-Templates:
    ```html
    {namespace i=WapplerSystems\Inquiry\ViewHelpers}
    <i:button.toggleItem uid="{product.uid}" />
    ```

## 🏗 Events

Nutzen Sie die folgenden Events für individuelle Anpassungen:

- `BuildInquiryFormEvent`: Passt die Formular-Definition an.
- `ResolveItemEvent`: Löst eine UID in ein Objekt auf.
- `CreateEmailToReceiverFinisherEvent`: Ermöglicht die Anpassung der E-Mail-Empfänger.
- ... und viele weitere.

---

## Authors

* [Sven Wappler](https://github.com/svewap) - [wappler.systems](https://wappler.systems)
* [Ilja Melnicenko](https://github.com/ille216) - [wappler.systems](https://wappler.systems)

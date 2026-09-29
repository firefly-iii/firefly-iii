# firefly-iii-multisource

**Inoffizieller Fork von [Firefly III](https://github.com/firefly-iii/firefly-iii) – vom Upstream-Projekt nicht unterstützt.**
Probleme bitte erst auf einer unveränderten Instanz nachstellen, bevor sie upstream gemeldet werden.

## Was ist anders?
- Split-**Ausgaben** dürfen verschiedene **Quellkonten** haben (z. B. teils Girokonto, teils Gutschein).
- Split-**Einnahmen** dürfen verschiedene **Zielkonten** haben.
- Transfers bleiben unverändert (gleiche Quelle + Ziel).

Geändert (siehe `multisource.patch`): Validierung beim Anlegen/Bearbeiten, die stille
Vereinheitlichung der Konten bei Updates und `correction:group-accounts`, sowie die
Split-Logik der Transaktionsformulare. Dazu der Regressionstest `MultiSourceSplitTest`.

## Wie Releases entstehen
`.github/workflows/multisource-release.yml` prüft täglich auf neue Upstream-Releases,
wendet den Patch an, führt den Regressionstest aus, baut das Frontend und veröffentlicht
`FireflyIII-multisource-<tag>.zip`. Scheitert Patch, Wächter oder Test, gibt es **kein** Release.

## ⚠️ Wichtig für den Betrieb
- **Niemals** das Upstream-/Community-`update` ausführen: Es installiert Original-Firefly, und
  `firefly-iii:upgrade-database` bucht alle Splits mit mehreren Quellkonten **still** auf ein Konto um.
- Im LXC stattdessen `firefly-update` nutzen (`.multisource/firefly-update.sh`, mit `--install-guard` einrichten).
- **Rückkehr zu Upstream:** vorher alle Split-Buchungen mit mehreren Quell-/Zielkonten in Einzelbuchungen aufteilen.

## Patch aktualisieren
Nach Änderungen am Code: `git diff upstream-tag -- app resources tests > .multisource/multisource.patch`

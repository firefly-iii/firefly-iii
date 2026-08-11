# Code Review Graph — Firefly III v6.6.4

Generated: 2026-07-04 | Project: Uang Pondok Bibit (`uang.jualayamsemarang.com`)

---

## 1. Project Anatomy

```
firefly-iii (v6.6.4)
├── 391 files / 89,022 lines
├── 1,301 functions / 54 classes (JS-indexed)
├── 22+ PHP service providers (Laravel backend)
├── 59 database migrations
├── 2 frontend versions coexisting
│
├── app/                          ← PHP backend (Laravel MVC)
│   ├── Http/Controllers/         ← Route handlers
│   ├── Providers/ (22+)          ← DI service wiring
│   ├── Events/                   ← Domain events (Account, Transaction, etc.)
│   ├── Models/                   ← Eloquent models
│   └── Console/                  ← Artisan commands
│
├── resources/assets/
│   ├── v1/                       ← jQuery + AdminLTE (legacy UI)
│   │   └── src/locales/ (36 files)
│   └── v2/                       ← Vue.js SPA (modern UI)
│       ├── src/api/v1/           ← API client classes (accounts, budgets, transactions…)
│       ├── src/api/v2/           ← v2 API client classes
│       ├── src/pages/            ← Page components
│       │   ├── dashboard/ (8 files)
│       │   └── transactions/shared/ (12 files)
│       ├── src/support/          ← Utilities (ag-grid, charts, editable)
│       └── src/libraries/dark-editable/  ← Inline editing component
│
├── public/v1/                    ← Built assets
│   ├── js/lib/vue.js             ← Vue.js 2.x (full dev build, 11k+ lines)
│   ├── js/lib/moment/ (35 files) ← Moment.js + locales
│   ├── js/lib/typeahead/         ← Typeahead.js (duplicated: bundle + source)
│   ├── js/lib/leaflet/           ← Leaflet maps (duplicated: ESM + CJS)
│   ├── js/ff/                    ← Firefly III app JS
│   │   ├── accounts/             ← Account CRUD pages
│   │   ├── transactions/         ← Transaction pages
│   │   ├── rules/                ← Rule engine UI
│   │   ├── budgets/              ← Budget management
│   │   └── ...
│   └── lib/adminlte/             ← AdminLTE dashboard framework
│
├── database/migrations/ (59)     ← DB schema evolution
├── .github/workflows/ (10)       ← CI/CD
└── storage/                      ← Runtime data (cache, logs, uploads)
```

---

## 2. Architecture Layers

```
┌─────────────────────────────────────────────────┐
│  BROWSER                                        │
│  ┌──────────────┐  ┌──────────────────────────┐ │
│  │  v1 (jQuery)  │  │  v2 (Vue.js SPA)         │ │
│  │  AdminLTE     │  │  ag-grid + dark-editable  │ │
│  │  Bootstrap    │  │  API client classes       │ │
│  └──────┬───────┘  └────────────┬─────────────┘ │
└─────────┼───────────────────────┼────────────────┘
          │ HTTP                  │ API (JSON)
┌─────────┼───────────────────────┼────────────────┐
│  NGINX  │  (SSL via Let's Encrypt)               │
│  FastCGI cache → PHP 8.5-FPM                    │
└─────────┼────────────────────────────────────────┘
          │
┌─────────┼────────────────────────────────────────┐
│  LARAVEL 13.x                                    │
│  ┌──────┴──────┐  ┌──────────┐  ┌────────────┐  │
│  │ Controllers  │  │ Events   │  │ Providers  │  │
│  │ (HTTP layer) │  │ (domain) │  │ (DI wiring)│  │
│  └──────┬──────┘  └──────────┘  └────────────┘  │
│         │                                        │
│  ┌──────┴──────┐  ┌──────────┐                  │
│  │ Models      │  │ Services │                   │
│  │ (Eloquent)  │  │ (business│                   │
│  └──────┬──────┘  │  logic)  │                   │
│         │         └──────────┘                   │
└─────────┼────────────────────────────────────────┘
          │
┌─────────┼────────────────────────────────────────┐
│  DATA LAYER                                      │
│  ┌──────┴──────┐  ┌──────────────┐              │
│  │ PostgreSQL  │  │ DragonflyDB  │               │
│  │ (10.88.0.254)│  │ (10.88.0.77) │              │
│  │ DB: firefly │  │ cache + sess │               │
│  └─────────────┘  └──────────────┘              │
└─────────────────────────────────────────────────┘
```

---

## 3. Complexity Hotspots (Top 20)

| Tier | Score | Lines | Function | File | Notes |
|------|-------|-------|----------|------|-------|
| T0 | **453.1** | 717 | `createPatchFunction()` | `public/v1/js/lib/vue.js:5859` | Vue.js VDOM patcher — library code |
| T0 | **96.7** | 74 | `spliceErrorsIntoTransactions()` | `v2/.../splice-errors-into-transactions.js:33` | **🔴 App code — needs review** |
| T0 | **70.9** | 118 | `processSlotContent()` | `public/v1/js/lib/vue.js:10122` | Vue.js compiler — library code |
| T0 | **28.4** | 53 | `genScopedSlots()` | `public/v1/js/lib/vue.js:11236` | Vue.js compiler — library code |
| T1 | **183.6** | 322 | `parse()` | `public/v1/js/lib/vue.js:9607` | Vue.js template parser — library code |
| T1 | **175.3** | 366 | `_init()` | `public/v1/lib/adminlte/js/app.js:229` | AdminLTE init — library code |
| T1 | **174.6** | 252 | `parseHTML()` | `public/v1/js/lib/vue.js:9300` | Vue.js HTML parser — library code |
| T1 | **124.3** | 96 | `updateTriggerInput()` | `public/v1/js/ff/rules/create-edit.js:313` | **🟠 App code — rule trigger UI** |
| T1 | **121.8** | 1 | `_init()` | `public/v1/lib/adminlte/js/app.min.js:13` | Minified AdminLTE — ignore |
| T1 | **90.1** | 132 | `processAttrs()` | `public/v1/js/lib/vue.js:10285` | Vue.js compiler — library code |
| T1 | **86.8** | 121 | `resolveAsyncComponent()` | `public/v1/js/lib/vue.js:3632` | Vue.js runtime — library code |
| T1 | **86.5** | 150 | `enter()` | `public/v1/js/lib/vue.js:8130` | Vue.js transition — library code |
| T1 | **78.3** | 81 | `parseFilters()` | `public/v1/js/lib/vue.js:6843` | Vue.js compiler — library code |
| T1 | **74.3** | 76 | `updateActionInput()` | `public/v1/js/ff/rules/create-edit.js:231` | **🟠 App code — rule action UI** |
| T1 | **71.9** | 103 | `leave()` | `public/v1/js/lib/vue.js:8281` | Vue.js transition — library code |
| T1 | **65.9** | 83 | `processRelativeTime()` | `public/v1/js/lib/moment/sl_SI.js:7` | Moment.js locale — library code |
| T1 | **62.9** | 93 | `getViewRange()` | `v2/src/support/get-viewrange.js:32` | **🟡 App code — date range logic** |
| T1 | **58.6** | 97 | `drawChart()` | `public/v1/js/ff/index.js:30` | **🟡 App code — dashboard charting** |
| T1 | **58.3** | 76 | `downloadSubscriptions()` | `v2/.../dashboard/subscriptions.js:133` | **🟡 App code — subscription export** |
| T1 | **58.7** | 59 | `translate()` | `public/v1/js/lib/moment/cs_CZ.js:22` | Moment.js locale — library code |

**Key insight**: Vue.js core + AdminLTE + Moment.js (library code) account for 12 of the top 20 hotspots. The **5 application hotspots worth reviewing** are in transaction error splicing (T0), rule trigger/action UI (T1), date range calculation (T1), dashboard charting (T1), and subscription downloads (T1).

---

## 4. Entry Points — Application Code

### v1 (jQuery pages)

| Function | File | Purpose |
|----------|------|---------|
| `startExport()` | `public/v1/js/ff/export/index.js:45` | Export workflow entry |
| `callExport()` | `public/v1/js/ff/export/index.js:95` | Trigger export job |
| `checkStatus()` | `public/v1/js/ff/export/index.js:138` | Poll export job status |
| `createCookie()` | `public/v1/js/ff/rules/index.js:32` | Rule group state persistence |
| `moveRuleGroup()` | `public/v1/js/ff/rules/index.js:62` | Drag-to-reorder rule groups |
| `duplicateRule()` | `public/v1/js/ff/rules/index.js:76` | Clone existing rule |
| `testRuleTriggers()` | `public/v1/js/ff/rules/index.js:170` | Dry-run rule triggers |
| `onAddNewAction()` | `public/v1/js/ff/rules/create-edit.js:173` | Rule action form builder |
| `onAddNewTrigger()` | `public/v1/js/ff/rules/create-edit.js:202` | Rule trigger form builder |
| `drawChart()` | `public/v1/js/ff/index.js:30` | Dashboard chart renderer |
| `getPiggyBanks()` | `public/v1/js/ff/index.js:131` | Dashboard piggy bank widget |
| `startSearch()` | `public/v1/js/ff/search/index.js:29` | Global search entry |
| `getReportOptions()` | `public/v1/js/ff/reports/index.js:88` | Report form builder |
| `catchSubmit()` | `public/v1/js/ff/reports/index.js:147` | Report form submission |
| `addMoney()` | `public/v1/js/ff/piggy-banks/index.js:65` | Piggy bank deposit |
| `removeMoney()` | `public/v1/js/ff/piggy-banks/index.js:75` | Piggy bank withdrawal |
| `submitTest()` | `public/v1/js/ff/preferences/index.js:37` | Preferences save handler |

### v2 (Vue.js SPA)

All v2 API classes (`resources/assets/v2/src/api/`) serve as entry points:
- `v1/api/` — 19 classes: accounts, budgets, transactions, currencies, piggy banks, subscriptions, user-groups, charts
- `v2/api/` — 14 classes (subset mirroring v1)
- `v1/chart/` + `v2/chart/` — Dashboard chart data fetchers

Key page entry points in `resources/assets/v2/src/pages/`:
- `dashboard/` (8 files) — Main dashboard widgets
- `transactions/shared/` (12 files) — Transaction create/edit shared logic

---

## 5. Semantic Duplicates — Consolidation Candidates

**242 duplicate groups** found (down from 249 in prior scan). Key consolidation opportunities:

### High-value deduplication targets:

| Pattern | Count | Files | Action |
|---------|-------|-------|--------|
| **Typeahead bundle vs source** | ~50 groups | `bloodhound.js` ↔ `typeahead.bundle.js` | Remove unbundled source, keep bundle only |
| **Leaflet ESM ↔ CJS** | ~30 groups | `leaflet-src.esm.js` ↔ `leaflet-src.js` | Pick one format |
| **create ↔ edit pairs** | 8+ groups | `recurring/create.js` ↔ `recurring/edit.js` | Extract shared module |
| **create ↔ edit pairs** | 2 groups | `accounts/create.js` ↔ `accounts/edit.js` | Extract shared module |
| **create ↔ edit pairs** | 1 group | `budgets/create.js` ↔ `budgets/edit.js` | Extract shared module |
| **Cookie utils** | 2 groups | `reports/all.js` ↔ `rules/index.js` | Move to shared utility |
| **v1/v2 API class overlap** | ~14 classes | `v1/api/` ↔ `v2/api/` | De-duplicate during v1→v2 migration |

---

## 6. Dead Code — Pruning Candidates

**1,352 unreferenced symbols** identified. Top clusters:

| Module | Dead Count | Notes |
|--------|-----------|-------|
| `leaflet-src.esm.js` + `leaflet-src.js` | ~400+ | Two copies of full Leaflet — most functions unused |
| `moment/` locales (35 files) | ~200+ | Only 2-3 locales likely needed (id, en) |
| `accounts/reconcile.js` | 14 functions | `selectAllReconcile`, `storeReconcile`, `calculateBalanceDifference`, `getTransactionsForRange`, `startReconcile`, `updateDifference`, etc. — reconciliation page may be deprecated |
| `budgets/index.js` | 11 functions | `updateBudgetedAmount`, `sortStop`, `createBudgetLimit`, `editBudgetLimit`, `showBudgetLimit`, `deleteBudgetLimit`, `drawBudgetedBars`, `drawSpentBars`, etc. |
| `auth/register.js` | 3 functions | `showErrors`, `sha1Hex`, `isPwned` — security feature (pwned password check) unused |
| `charts.js` | 2 functions | `colorizeData`, `lineChart` — may be dead chart utilities |
| `admin/users.js` | 1 function | `deleteInvite` — invite deletion unused |
| `bills/index.js` | 1 function | `stopSorting` — sort cleanup unused |
| `accounts/index.js` | 1 function | `stopSorting` — sort cleanup unused |
| `accounts/create.js` + `edit.js` | 1 each | `triggerDirection()` — duplicate dead function |
| `budgets/create.js` + `edit.js` | 1 each | `updateAutoBudget()` — duplicate dead function |

---

## 7. Dependency Health

### Clean: ✅
- **No import cycles** detected — well-structured dependency graph
- **No circular class dependencies** among the 54 JS-indexed classes
- PHP service providers follow clean Laravel DI pattern

### Concerning: ⚠️
- **Two frontend versions coexist** (v1 jQuery, v2 Vue.js) — migration in progress, double maintenance burden
- **Duplicate library copies**: `typeahead.bundle.js` + `bloodhound.js` + `typeahead.jquery.js` (3 copies of same logic); `leaflet-src.esm.js` + `leaflet-src.js` (2 copies)
- **Vue.js full dev build** shipped in production (`public/v1/js/lib/vue.js`, 11k+ lines) — should be minified production build
- **v1/v2 API client duplication**: Each model has both v1 and v2 API classes with overlapping logic
- **AdminLTE duplicated**: Both `app.js` (366 lines) and `app.min.js` (1 line, minified) shipped — the minified build has phantom complexity score of 121.8

---

## 8. Git Status — Working Tree Changes (2026-07-04)

```
Branch: HEAD (detached)
Unstaged: 26 files
  - bootstrap/cache/.gitignore
  - composer.json / composer.lock (PHP dependency changes)
  - 5 OAuth migration deletions (2018_01_01 dates)
  - package-lock.json
  - 14 storage/.gitignore modifications
  - 2 storage/build test files (ebcdic.txt, test-upload.csv)
  - storage/.htaccess
Untracked: 7 files
  - CLAUDE.md (project documentation)
  - CODE-REVIEW-GRAPH.md (this file)
  - .token-savior-cache.json (tool cache)
  - 4 OAuth migration additions (2016_06_01 dates — duplicate of deleted 2018 versions)
```

**⚠️ OAuth migration conflict**: Two sets of Passport migrations exist (2016 and 2018 dates). The 2018 versions are staged for deletion while 2016 versions are untracked additions. This matches the CLAUDE.md warning: "DO NOT run `passport:install` again — it publishes duplicate migration files that conflict with existing tables."

---

## 9. Review Priority Matrix

| Priority | Area | Issue | Effort | Impact |
|----------|------|-------|--------|--------|
| 🔴 P0 | `spliceErrorsIntoTransactions()` | T0 complexity (96.7) — 74 lines, 35 branches, business logic hotspot | Medium | High |
| 🔴 P0 | OAuth migrations | Duplicate migration files (2016 vs 2018) — resolve conflict | Low | High |
| 🟠 P1 | create/edit duplication | 11+ symbol pairs duplicated across create/edit pages | Medium | Medium |
| 🟠 P1 | `updateTriggerInput()` + `updateActionInput()` | T1 complexity (124.3 + 74.3) in rule engine UI | Medium | Medium |
| 🟠 P1 | Production Vue.js build | Full dev build (11k lines) + unminified AdminLTE in production assets | Low | Medium |
| 🟠 P1 | `accounts/reconcile.js` | 14 dead functions — confirm deprecated, then remove | Low | Medium |
| 🟡 P2 | Leaflet duplication | Two full copies of Leaflet library (ESM + CJS) | Low | Low |
| 🟡 P2 | Moment.js locales | 35 locale files, most unused; keep only id + en | Low | Low |
| 🟡 P2 | Dead code (1,352 symbols) | Gradual cleanup — start with `budgets/index.js` (11 dead), `auth/register.js` (3 dead) | Medium | Low |
| 🟢 P3 | Typeahead duplication | Three copies of same library (bloodhound + bundle + jquery) | Low | Low |
| 🟢 P3 | v1/v2 API class overlap | Parallel API client classes for both frontend versions | High | Medium |

---

## 10. Graph Metrics Summary

| Metric | Value | Δ from 2026-07-01 |
|--------|-------|-------------------|
| Total files | 391 | +1 |
| Total lines | 89,022 | +240 |
| Functions (indexed) | 1,301 | — |
| Classes (indexed) | 54 | — |
| PHP service providers | 22+ | — |
| DB migrations | 59 | — |
| App entry points (v1 JS) | ~17 | — |
| Complexity hotspots (T0) | 4 | — |
| Complexity hotspots (T1) | 16 | — |
| Dead symbols | 1,352 | — |
| Semantic duplicate groups | 242 | -7 |
| Import cycles | 0 | — |
| Frontend versions | 2 (v1 jQuery, v2 Vue.js) | — |
| API client versions | 2 (v1, v2) | — |
| Working tree changes | 26 unstaged, 7 untracked | +12 unstaged |

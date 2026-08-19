# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Primary user: a tech-savvy, privacy-conscious individual (or household) who self-hosts their own personal-finance data rather than uploading it to a cloud budgeting service. Comfortable running and maintaining a self-hosted server/Docker install. In this repo, the working instance has two real accounts (an owner/site-owner and a second independently-registered user, each with their own separate administration), consistent with Firefly III's support for multiple administrations sharing one installation, though a single-user administration is the primary tested scenario here.

## Product Purpose

Firefly III is a self-hosted personal finance manager: track income and expenses, budget with categories/budgets/tags, and get reports/insight into where money goes, so the user can act on it (spend less, save more, stop living paycheck to paycheck). Per the project's own framing: "Money should be useful, not scary" — insight and control, not another cloud dashboard to distrust.

## Positioning

Fully self-hosted and isolated — never contacts external servers unless explicitly told to — which a cloud-hosted budgeting competitor (Mint-style, YNAB-cloud, etc.) cannot truthfully claim. Combined with a REST API covering nearly the whole app, double-entry bookkeeping, a rules engine, recurring transactions, and any-currency support, aimed at users who want real ownership of their financial data, not just a nicer chart.

## Operating Context

Self-managed install (bare server, Docker, Kubernetes, Cloudron, etc.) run by the user themselves — there's no vendor support line, so the UI is the entire support surface. Used both for day-to-day entry/review (transactions, budgets, piggy banks) and periodic review (reports, rules maintenance). Data may be imported from external tools via the API/import pipeline. In this repo specifically: local dev instance, SQLite-backed, two real user accounts with real (test) accounts/currencies already in use.

## Capabilities and Constraints

Core capabilities already shipped and in active use this session, all of which must keep working exactly as before after the visual revamp (explicit constraint: **no functionality may go missing or regress**): asset/expense/revenue/liabilities accounts, double-entry transactions (withdrawals/deposits/transfers), budgets, categories, tags, recurring transactions, rule-based automation, piggy banks (savings goals), subscriptions/bills, multi-currency support with a per-administration primary currency (any user can set their own administration's primary currency; only the site owner may create/edit/delete currency *definitions*, which are shared, installation-wide data), reports/charts, webhooks, 2FA, a REST API, and multi-administration support.

Technical constraint for this pass: `resources/assets/v1` is the **live** app — every route currently renders it (Twig views, Bootstrap 3 / AdminLTE 2, jQuery + Vue 2 components). A separate `resources/assets/v2` exists in the repo (Bootstrap 5 / AdminLTE 4 / Alpine.js) but is not wired to any controller or route — it is out of scope for this revamp. Routing, controllers, and backend behavior stay untouched; this is a visual/front-end revamp of the existing live surface, not a framework migration.

Terminology: an "administration" is Firefly III's term for what a user might think of as a household/shared-finances group; a user's own administration is created automatically on registration.

## Brand Commitments

Product name "Firefly III" and its flame/firefly logo mark are existing, real brand assets (not to be silently discarded or replaced with a fabricated new name/logo without being asked) — but see Positioning: this is a personal-instance revamp, so the visual treatment of that identity (color, mark styling, wordmark treatment) is open for reinvention in new-work, not fixed here.

## Evidence on Hand

Real, running local instance with real (test) data created during this working session: two user accounts (`jude.bellsandra@gmail.com`, site owner; `sudarshanshetty078@gmail.com`, a second independent administration), asset accounts ("Karnataka Bank", "Karnataka Bank savings account", "Cash wallet"), and multiple enabled currencies (EUR, INR) with a per-administration primary currency. No fabricated demo content should be introduced where this real data can be used instead; no testimonials, benchmarks, or pricing exist for this product and none should be invented.

## Product Principles

- Insight and control over money, not another dashboard to distrust — the UI should make "where is my money going" answerable at a glance, not buried in menus.
- Self-hosted trust: nothing about the redesign should make the product feel like a SaaS/cloud product it explicitly isn't.
- Every existing capability keeps working. This pass is a visual and information-architecture revamp of the live `v1` surface, not a functionality cut or a backend rewrite.
- Reduce duplication and repeated/boring patterns the user has already flagged (e.g. redundant buttons/titles, dense unstyled AdminLTE 2 defaults) in favor of a coherent, deliberate design system.
- Personal-instance context: full creative liberty on visual direction; no obligation to match upstream Firefly III's shipped look.

## Accessibility & Inclusion

No project-specific accessibility requirement was established beyond ordinary web standards; existing UI uses standard form labels, ARIA attributes on a few components (e.g. the currency toggle switch built this session), and keyboard-operable Bootstrap components. Preserve or improve on this baseline; do not regress it.

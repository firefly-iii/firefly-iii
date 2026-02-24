# Norway Golden-Standard Firefly Playbook

## Purpose
Apply a privacy-first, evidence-ranked personal finance workflow in Firefly III to support:
1. Accounting integrity
2. Actionable financial intelligence
3. Long-term clarity and wealth optimization
4. Independence from third-party custodians

---

## 1) Firefly Configuration Baseline

### Currency and administration
- Set primary currency to NOK.
- Use one administration per legal/economic household.

### Account model (minimum viable)
- Asset accounts:
  - Main checking
  - Savings / buffer
  - Secondary payment account(s)
- Revenue accounts:
  - Salary
  - Bonus
  - Refund / reimbursement income
  - Interest income
- Expense accounts (counterparties/payees created as needed):
  - Groceries
  - Housing
  - Utilities
  - Transport
  - Health
  - Insurance
  - Childcare / education
  - Leisure
  - Subscriptions
  - Taxes / fees
- Liabilities:
  - Mortgage
  - Student debt
  - Credit account(s)

### Transaction principles
- Withdrawals: source is asset/liability, destination is expense.
- Deposits: source is revenue, destination is asset/liability.
- Transfers: asset to asset only.
- Enter positive amounts only and rely on transaction type semantics.

---

## 2) Goal Structure in Firefly

Create goals with explicit metadata in the title/notes:
- Target amount (NOK)
- Deadline (YYYY-MM)
- Priority (P1/P2/P3)
- Flexibility (low/medium/high)

Suggested goal classes:
- Liquidity safety
- Debt optimization
- Medium-term purchase
- Retirement / long-term capital
- Legacy / estate

Use piggy banks for earmarked savings buckets where useful.

---

## 3) Risk Tolerance Model (Willingness / Ability / Need)

Store a quarterly “risk profile” note in Firefly (or external secure note):
- Willingness score: 1-5
- Ability score: 1-5
- Need score: 1-5
- Final risk class = minimum of the three

Mapping:
- 1 Defensive
- 2 Cautious
- 3 Balanced
- 4 Growth
- 5 Aggressive

Operational rule:
- If scores diverge, use the lower class for decisions until next quarterly review.

---

## 4) Cash-Flow Constraints in Firefly

Create a monthly dashboard workflow:
- Net income after tax
- Fixed obligations
- Essential variable costs
- Minimum debt service
- Free cash flow = net income - fixed obligations - essentials - minimum debt service

Guardrails:
- Emergency buffer target must stay funded.
- Debt service ratio must remain within your defined threshold.
- New investments come from recurring monthly surplus, not one-off gains (unless explicitly assigned).

Stress checks (monthly):
- Income down 10%
- Essential expenses up 20%
- Plan remains viable or has a corrective path.

---

## 5) Tax Jurisdiction (Norway) Handling

Use primary-source updates from:
- Skatteetaten
- Finanstilsynet
- Norges Bank
- Finansdepartementet

Operational checklist:
- Confirm tax residency status (and cross-border effects where relevant).
- Classify income correctly (salary, interest, dividends, gains, etc.).
- Classify liabilities and deductible items consistently.
- Reconcile annual totals before filing.
- Re-check rule/rate assumptions each tax year.

Do not hard-code rates in Firefly notes across years; use dated references.

---

## 6) Rules / Categories / Tags for Actionable Intelligence

### Categories (stable taxonomy)
Use a compact, durable category set, such as:
- Housing
- Food
- Transport
- Health
- Insurance
- Children
- Work
- Leisure
- Taxes
- Financial costs
- Financial income

### Tags (cross-cutting dimensions)
Use tags for analysis dimensions, for example:
- essential
- discretionary
- recurring
- one-off
- reimbursable
- tax-relevant

### Rule automation (start with 10)
1. If description contains known supermarket names -> category Food, tag essential.
2. If description contains rent/mortgage keywords -> category Housing, tag recurring.
3. If description contains power/internet/mobile providers -> category Utilities, tag recurring.
4. If description contains transit/fuel operators -> category Transport.
5. If description contains pharmacy/medical providers -> category Health.
6. If description contains insurance providers -> category Insurance.
7. If incoming transaction from employer -> category Financial income, source Salary.
8. If description contains refund terms -> tag reimbursable and create follow-up flag.
9. If merchant is subscription vendor list -> category Leisure or Work as defined, tag recurring.
10. If bank fee/interest expense patterns -> category Financial costs.

Keep rules simple, deterministic, and reviewed monthly.

---

## 7) Operating Cadence

### Weekly (30-45 min)
- Enter or import transactions.
- Resolve uncategorized entries.
- Validate account balances against statements.
- Clear anomalies and duplicate candidates.

### Monthly (60-90 min)
- Reconcile all asset and liability accounts.
- Review category/budget variance.
- Update free cash flow and savings rate.
- Reclassify mis-tagged transactions.
- Adjust rule set for new merchants/patterns.

### Quarterly (90 min)
- Re-score risk tolerance (W/A/N).
- Re-rank goals by priority and timeline.
- Evaluate long-term trajectory (net worth, debt trend, savings durability).

---

## 8) Definition of Done per Month

A month is complete when all are true:
- All core accounts reconciled.
- Uncategorized transactions are below your threshold.
- Goal contributions are posted and tracked.
- Risk class and cash-flow constraints remain satisfied.
- Tax-relevant tagging is updated for filing readiness.

---

## 9) Minimal Start Sequence (this week)

1. Finalize account list and categories.
2. Backfill transactions from yesterday onward.
3. Apply first 10 rules.
4. Run first monthly report pack.
5. Fix top misclassifications.
6. Lock baseline and begin weekly cadence.

---

## 10) First 10 Merchant Rules (Norway Starter Pack)

Use this as your first live ruleset. Keep rule order as listed (most specific first).

1. NorgesGruppen groceries
  - Trigger contains any: `MENY`, `KIWI`, `SPAR`, `JOKER`
  - Action: category = Food, tag = essential

2. Coop / Rema groceries
  - Trigger contains any: `COOP`, `EXTRA`, `OBS`, `REMA 1000`
  - Action: category = Food, tag = essential

3. Housing recurring
  - Trigger contains any: `HUSLEIE`, `RENT`, `BORETTSLAG`, `FELLESKOST`, `MORTGAGE`
  - Action: category = Housing, tag = recurring

4. Utilities and telecom
  - Trigger contains any: `FJORDKRAFT`, `FORTUM`, `HAFSLUND`, `TELIA`, `TELENOR`, `ICE`
  - Action: category = Housing, tags = recurring, essential

5. Transport public transit and tolls
  - Trigger contains any: `RUTER`, `VY`, `NOR-WAY`, `FLYTOGET`, `AUTOPASS`
  - Action: category = Transport, tag = essential

6. Fuel and EV charging
  - Trigger contains any: `CIRCLE K`, `ESSO`, `SHELL`, `YX`, `TESLA SUPERCHARGER`, `MONTA`
  - Action: category = Transport

7. Pharmacy and healthcare
  - Trigger contains any: `APOTEK 1`, `VITUSAPOTEK`, `BOOTS APOTEK`, `LEGE`, `HELFO`
  - Action: category = Health, tag = essential

8. Salary and employer income
  - Trigger contains any: `LØNN`, `LONN`, `[EMPLOYER_NAME]`
  - Condition: transaction type is deposit
  - Action: category = Financial income, source account = Salary, tag = recurring

9. Government transfers/refunds
  - Trigger contains any: `NAV`, `SKATTEETATEN`, `TILBAKEBETALING`, `REFUSJON`
  - Action: category = Financial income, tag = tax-relevant

10. Bank fees and interest costs
   - Trigger contains any: `GEBYR`, `TERMINGEBYR`, `RENTE`, `KORTGEBYR`, `OVERTR` 
   - Action: category = Financial costs, tags = recurring, tax-relevant

Implementation note:
- Replace `[EMPLOYER_NAME]` with your exact payroll sender text from statement data.

---

## 11) Rule Tuning Workflow (Weekly 10 Minutes)

1. Open uncategorized transactions from the last 7 days.
2. If a merchant appears 2+ times, add/update one deterministic rule.
3. Prefer “contains exact merchant token” over broad keywords.
4. Keep one merchant in one primary category unless a split is truly needed.
5. Re-run reports and verify category drift is shrinking.

Quality threshold:
- Keep uncategorized transactions under 5% of monthly transaction count.

---

## 12) Personalization Slots (Fill Once)

- Primary payroll sender: `[EMPLOYER_NAME]`
- Main bank sender patterns: `[BANK_PATTERN_1]`, `[BANK_PATTERN_2]`
- Mortgage/lender text pattern: `[LENDER_PATTERN]`
- Subscription vendors list: `[SUB_1]`, `[SUB_2]`, `[SUB_3]`
- Frequent child/education merchants (optional): `[CHILD_1]`, `[CHILD_2]`

After filling these, duplicate the starter rules and swap placeholders for exact statement strings.

---

## 13) Monthly Report Pack (Actionable Intelligence)

Run this pack at month-end, in this order:

1. Income vs expense overview (monthly)
2. Category spend breakdown (monthly and 3-month trend)
3. Account balances and net worth trend
4. Debt balances and debt-service trend
5. Budget variance report (planned vs actual)
6. Recurring transactions consistency check

Capture each month in a short log:
- What changed?
- Why did it change?
- What action is required next month?

---

## 14) KPI Thresholds and Decision Rules

Define your baseline values once, then review monthly.

Core KPIs:
- Savings rate = (monthly surplus / net income after tax)
- Essentials ratio = (essential spend / total spend)
- Debt-service ratio = (minimum debt service / net income after tax)
- Uncategorized ratio = (uncategorized transactions / total transactions)
- Recurring coverage = (known recurring items correctly tagged / total recurring items)

Decision rules:
- If Uncategorized ratio > 5%: pause new analytics, fix rules/tags first.
- If Debt-service ratio breaches your limit: direct all surplus to debt optimization until restored.
- If Savings rate is below goal for 2 consecutive months: reduce discretionary spend categories first.
- If Essentials ratio rises unexpectedly for 2 months: review housing, transport, and food assumptions.
- If any account cannot be reconciled: mark month as not closed and resolve before planning decisions.

---

## 15) 90-Day Rollout Plan

Days 1-14:
- Finalize accounts, categories, tags, and first 10 rules.
- Backfill recent transactions.
- Complete first reconciliation cycle.

Days 15-45:
- Tune rules weekly.
- Add budgets for top variable categories.
- Add transaction links for reimbursements/refunds.

Days 46-75:
- Expand rule coverage to top 80% of merchants by frequency.
- Implement split transactions for mixed-purpose purchases.
- Start quarterly risk profile scoring (Willingness/Ability/Need).

Days 76-90:
- Run full monthly report pack and compare against goals.
- Document constraints, bottlenecks, and next-quarter targets.
- Freeze taxonomy changes unless materially necessary.

Exit criteria at day 90:
- Reconciliation complete for all core accounts.
- Uncategorized ratio consistently under 5%.
- Stable monthly review habit established.
- Goal contribution tracking active and reliable.

---

## 16) Golden-Standard Evidence Register

For each meaningful policy, tax, or strategy decision, keep a brief evidence line:

- Decision date
- Decision statement
- Source tier (Tier 1, 2, or 3)
- Primary source used (authority/publication)
- Assumptions
- Recheck date

Template:
- Date: [YYYY-MM-DD]
- Decision: [one sentence]
- Tier: [1/2/3]
- Source: [authority + page/title]
- Assumptions: [short list]
- Recheck: [YYYY-MM]

This keeps recommendations auditable and prevents stale assumptions from driving future actions.

---

## 17) TODO (Prioritized)

### Today
- [x] Verify Firefly is reachable at `http://127.0.0.1:8080`.
- [x] Confirm primary working currency is NOK in account creation and transaction forms.
- [ ] Enter 3-5 real transactions (withdrawal, deposit, transfer).
- [ ] Reconcile `Accounts > Asset accounts` with current statement balances.

### This week
- [x] Finalize account taxonomy (asset, revenue, expense, liabilities).
- [ ] Apply the first 10 merchant rules and test them on recent transactions.
- [ ] Keep uncategorized transactions under 5%.
- [ ] Save first month baseline snapshot (KPIs + notes).

### DNB connection (Golden-Standard method)
- [ ] Set up Firefly Data Importer (separate service) and confirm it is reachable.
- [ ] Create Enable Banking application in production mode.
- [ ] Set redirect URL to `https://<YOUR_DATA_IMPORTER_URL>/eb-callback`.
- [ ] Link DNB accounts in Enable Banking portal (required before import in restricted mode).
- [ ] Configure importer credentials (`ENABLE_BANKING_APP_ID`, `ENABLE_BANKING_PRIVATE_KEY`).
- [ ] Run first DNB import with content-based duplicate detection and import tag enabled.
- [ ] Map imported accounts/payees to existing Firefly accounts and save configuration for reuse.

Note:
- The DNB connection steps require web UI actions and external credentials/authorization, so they cannot be fully completed from terminal only.

### Fallback path (if DNB API connection is unavailable)
- [ ] Export CAMT.053 (preferred) or CSV from DNB.
- [ ] Import via Data Importer with duplicate detection set to content-based.
- [ ] Review and fix role mapping once, then reuse config for future imports.

### Month-end close
- [ ] Run monthly report pack (income/expense, category trends, net worth, debt service).
- [ ] Record KPI values and exceptions.
- [ ] Update Golden-Standard evidence register for any new policy/tax assumptions.

### Completed setup notes
- [x] Firefly app initialized with SQLite and migrations applied.
- [x] Frontend assets built (`public/build/manifest.json` present).
- [x] Locale fallback configured to avoid invalid monetary locale warning.
- [x] Baseline categories (11) and analysis tags (6) created from terminal.
- [x] Integrity report executed from terminal (`firefly-iii:report-integrity`).


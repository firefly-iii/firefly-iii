# Firefly III Gmail Statement Importer

A separately deployable companion service for staging emailed PDF statements
before importing transactions into Firefly III. It does not modify Firefly III
core files.

## Current status

The foundation provides a local status page, Gmail OAuth with the
`gmail.readonly` scope, sender allowlisting, PDF attachment size limits,
encrypted OAuth-token storage, and a SQLite staging queue. PDFs are processed
in memory and are not saved to disk. It is not connected to the Firefly III API
yet.

No bank-specific transaction parser is configured yet. Until parsers are added
and tested against representative statement templates, matching attachments
are marked `needs_parser`. This foundation does not infer or post transactions
to Firefly III. Scanned PDFs also require an OCR component, which is not
configured.

## Setup

1. Configure a Google Cloud OAuth consent screen and enable the Gmail API.
   Create a Web application OAuth client with the redirect URI
   `http://localhost:8082/oauth/callback` (or the HTTPS URL you will use).
   Download its client-secrets JSON as `google-oauth-client.json` in this
   directory. Grant only the requested Gmail read-only scope.
2. Copy `.env.example` to `.env`. Set a long unique `APP_PASSWORD`, a generated
   `GOOGLE_TOKEN_ENCRYPTION_KEY`, and exact bank statement sender addresses in
   `GMAIL_ALLOWED_SENDERS` (comma-separated).
3. Add `http://localhost:8082/oauth/callback` to the Google OAuth client's
   authorized redirect URIs, updating both the client and
   `GOOGLE_OAUTH_REDIRECT_URI` if using another URL.
4. Adapt `compose.yaml` to your installation. The sample Compose file is a
   standalone service definition and currently does not connect to Firefly III.
5. Start with `docker compose up --build -d`, open `http://localhost:8082`, and
   connect Gmail. Keep the port bound to localhost unless HTTPS and
   authentication are provided by a trusted reverse proxy.

Never commit `.env`, the OAuth client file, or the database volume. Keep the
database volume private because it contains sender and attachment metadata.
OAuth credentials are encrypted at rest; protect and back up the encryption
key separately from the database.

## Parser implementation

Add a reviewed parser for each bank in `app/parser.py` after obtaining
representative, redacted templates for ICICI, HSBC, RBL, IDFC FIRST, and IDBI.
Test debit/credit direction, dates, currency, multi-line descriptions, opening
and closing balances, and duplicate rows. Do not enable automatic posting;
the intended workflow is to review parsed transactions before sending them to
Firefly III.

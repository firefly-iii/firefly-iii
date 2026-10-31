from __future__ import annotations

import asyncio
import logging
from contextlib import asynccontextmanager
from typing import Annotated

from fastapi import Depends, FastAPI, HTTPException, status
from fastapi.responses import HTMLResponse, RedirectResponse
from fastapi.security import HTTPBasic, HTTPBasicCredentials
from google.auth.exceptions import RefreshError
from googleapiclient.errors import HttpError
from google_auth_oauthlib.flow import Flow

from app.config import Settings
from app.database import Database
from app.gmail import (
    PollResult,
    SCOPES,
    create_oauth_flow,
    handle_gmail_error,
    poll_gmail,
)
from app.security import TokenStore, credentials_match

logging.basicConfig(level=logging.INFO)
LOGGER = logging.getLogger(__name__)

settings = Settings.from_env()
database = Database(settings.database_path)
token_store = TokenStore(settings.encryption_key)
basic_auth = HTTPBasic()
oauth_flows: dict[str, Flow] = {}
poll_lock = asyncio.Lock()


async def run_poll() -> PollResult:
    async with poll_lock:
        return await asyncio.to_thread(
            poll_gmail, settings, database, token_store
        )


def require_auth(
    credentials: Annotated[HTTPBasicCredentials, Depends(basic_auth)],
) -> str:
    if not credentials_match(
        credentials.username,
        credentials.password,
        settings.username,
        settings.password,
    ):
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Invalid username or password",
            headers={"WWW-Authenticate": "Basic"},
        )
    return credentials.username


async def poll_loop() -> None:
    while True:
        try:
            result = await run_poll()
            database.set_poll_state(last_error=None)
            if result.scanned == 0 and database.get_token() is None:
                LOGGER.info("Gmail is not connected; authorize it in the UI.")
        except (HttpError, RefreshError) as error:
            database.set_poll_state(last_error=handle_gmail_error(error))
        except ValueError as error:
            message = str(error)
            database.set_poll_state(last_error=message)
            LOGGER.error("Gmail polling is not configured: %s", message)
        await asyncio.sleep(settings.poll_interval_seconds)


@asynccontextmanager
async def lifespan(_: FastAPI):
    task = asyncio.create_task(poll_loop())
    try:
        yield
    finally:
        task.cancel()
        try:
            await task
        except asyncio.CancelledError:
            pass


app = FastAPI(title="Firefly III Gmail Statement Importer", lifespan=lifespan)


@app.get("/", response_class=HTMLResponse, dependencies=[Depends(require_auth)])
def home() -> str:
    return """
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width">
<title>Statement importer</title>
<style>
body{font:16px system-ui;max-width:900px;margin:2rem auto;padding:0 1rem;color:#222}
button{padding:.55rem .8rem}table{border-collapse:collapse;width:100%;margin-top:1rem}
th,td{text-align:left;padding:.6rem;border-bottom:1px solid #ddd;vertical-align:top}
.muted{color:#555}.error{color:#9b1c1c}
</style></head>
<body>
<h1>Gmail statement importer</h1>
<p>Statements are staged locally. No transactions are sent to Firefly III by this foundation.</p>
<p><a href="/oauth/start">Connect Gmail (read-only access)</a></p>
<p id="poll" class="muted">Loading status…</p>
<table><thead><tr><th>Date received</th><th>Bank sender</th><th>Attachment</th><th>Status</th></tr></thead>
<tbody id="imports"></tbody></table>
<script>
async function load(){
 const [s,r]=await Promise.all([fetch('/api/status'),fetch('/api/imports')]);
 const status=await s.json(), rows=await r.json();
 document.getElementById('poll').textContent=
   'Last poll: '+(status.last_poll_at||'not yet run')+
   (status.last_error?' — '+status.last_error:'');
 document.getElementById('poll').className=status.last_error?'error':'muted';
 const body=document.getElementById('imports'); body.replaceChildren();
 for(const item of rows){
   const tr=document.createElement('tr');
   for(const value of [item.created_at,item.bank,item.filename,item.status+
     (item.details?' — '+item.details:'')]){
     const td=document.createElement('td'); td.textContent=value; tr.append(td);
   }
   body.append(tr);
 }
}
load(); setInterval(load,15000);
</script></body></html>
"""


@app.get("/health")
def health() -> dict[str, str]:
    return {"status": "ok"}


@app.get("/api/status", dependencies=[Depends(require_auth)])
def api_status() -> dict[str, str | bool | None]:
    state = database.get_poll_state()
    state["gmail_connected"] = database.get_token() is not None
    return state


@app.get("/api/imports", dependencies=[Depends(require_auth)])
def api_imports() -> list[dict[str, object]]:
    return database.list_imports()


@app.get("/oauth/start", dependencies=[Depends(require_auth)])
def oauth_start() -> RedirectResponse:
    flow = create_oauth_flow(settings)
    authorization_url, state = flow.authorization_url(
        access_type="offline",
        include_granted_scopes="true",
        prompt="consent",
    )
    oauth_flows[state] = flow
    return RedirectResponse(authorization_url, status_code=302)


@app.get("/oauth/callback")
def oauth_callback(state: str, code: str) -> RedirectResponse:
    flow = oauth_flows.pop(state, None)
    if flow is None:
        raise HTTPException(
            status_code=400,
            detail="OAuth state is invalid or expired.",
        )
    flow.fetch_token(code=code)
    credentials = flow.credentials
    if not set(SCOPES).issubset(set(credentials.scopes or [])):
        raise HTTPException(
            status_code=403,
            detail="Gmail read-only authorization was not granted.",
        )
    database.put_token(token_store.encrypt_credentials(credentials))
    return RedirectResponse("/", status_code=303)


@app.post("/api/poll", dependencies=[Depends(require_auth)])
async def api_poll() -> dict[str, int]:
    try:
        result = await run_poll()
    except (HttpError, RefreshError) as error:
        message = handle_gmail_error(error)
        database.set_poll_state(last_error=message)
        raise HTTPException(status_code=502, detail=message) from error
    except ValueError as error:
        database.set_poll_state(last_error=str(error))
        raise HTTPException(status_code=400, detail=str(error)) from error
    database.set_poll_state(last_error=None)
    return {
        "scanned": result.scanned,
        "staged": result.staged,
        "skipped": result.skipped,
    }

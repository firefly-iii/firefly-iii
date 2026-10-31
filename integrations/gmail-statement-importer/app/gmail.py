from __future__ import annotations

import base64
import logging
from dataclasses import dataclass
from email.utils import parseaddr
from typing import Any

from google.auth.exceptions import RefreshError
from google.auth.transport.requests import Request
from googleapiclient.discovery import Resource, build
from googleapiclient.errors import HttpError
from google_auth_oauthlib.flow import Flow

from app.config import Settings
from app.database import Database
from app.parser import UnsupportedStatementError, parse_statement
from app.security import TokenStore

LOGGER = logging.getLogger(__name__)
SCOPES = ["https://www.googleapis.com/auth/gmail.readonly"]


@dataclass(frozen=True)
class PollResult:
    scanned: int
    staged: int
    skipped: int


def create_oauth_flow(settings: Settings) -> Flow:
    flow = Flow.from_client_secrets_file(
        str(settings.oauth_client_secrets),
        scopes=SCOPES,
        redirect_uri=settings.oauth_redirect_uri,
    )
    return flow


def gmail_service(
    database: Database, token_store: TokenStore
) -> Resource | None:
    encrypted_token = database.get_token()
    if encrypted_token is None:
        return None

    credentials = token_store.decrypt_credentials(encrypted_token)
    if credentials.expired and credentials.refresh_token:
        credentials.refresh(Request())
        database.put_token(token_store.encrypt_credentials(credentials))
    if not credentials.valid:
        raise RefreshError("Gmail authorization is no longer valid.")
    return build("gmail", "v1", credentials=credentials, cache_discovery=False)


def _sender(message: dict[str, Any]) -> str:
    headers = message.get("payload", {}).get("headers", [])
    for header in headers:
        if header.get("name", "").lower() == "from":
            return parseaddr(header.get("value", ""))[1].lower()
    return ""


def _pdf_parts(payload: dict[str, Any]) -> list[dict[str, str]]:
    result: list[dict[str, str]] = []
    stack = [payload]
    while stack:
        part = stack.pop()
        filename = part.get("filename", "")
        body = part.get("body", {})
        attachment_id = body.get("attachmentId")
        mime_type = part.get("mimeType", "").lower()
        if filename.lower().endswith(".pdf") and attachment_id:
            if mime_type in {"application/pdf", "application/octet-stream", ""}:
                result.append(
                    {"filename": filename, "attachment_id": attachment_id}
                )
        stack.extend(part.get("parts", []))
    return result


def _read_attachment(
    service: Resource,
    message_id: str,
    attachment_id: str,
    max_attachment_bytes: int,
) -> bytes:
    attachment = (
        service.users()
        .messages()
        .attachments()
        .get(userId="me", messageId=message_id, id=attachment_id)
        .execute()
    )
    encoded = attachment.get("data", "")
    estimated_size = (len(encoded) * 3) // 4
    if estimated_size > max_attachment_bytes:
        raise UnsupportedStatementError(
            f"PDF attachment exceeds the {max_attachment_bytes}-byte limit."
        )
    data = base64.urlsafe_b64decode(encoded + "=" * (-len(encoded) % 4))
    if len(data) > max_attachment_bytes:
        raise UnsupportedStatementError(
            f"PDF attachment exceeds the {max_attachment_bytes}-byte limit."
        )
    return data


def poll_gmail(
    settings: Settings, database: Database, token_store: TokenStore
) -> PollResult:
    if not settings.allowed_senders:
        raise ValueError("Set GMAIL_ALLOWED_SENDERS before polling Gmail.")
    service = gmail_service(database, token_store)
    if service is None:
        return PollResult(scanned=0, staged=0, skipped=0)

    senders = " ".join(sorted(settings.allowed_senders))
    query = f"has:attachment filename:pdf from:{{{senders}}}"
    page = (
        service.users()
        .messages()
        .list(userId="me", q=query, maxResults=50)
        .execute()
    )
    message_refs = page.get("messages", [])
    staged = 0
    skipped = 0

    for message_ref in message_refs:
        message_id = message_ref["id"]
        message = (
            service.users()
            .messages()
            .get(userId="me", id=message_id, format="full")
            .execute()
        )
        sender = _sender(message)
        if sender not in settings.allowed_senders:
            continue
        bank = sender
        for part in _pdf_parts(message.get("payload", {})):
            attachment_id = part["attachment_id"]
            if database.has_import(message_id, attachment_id):
                skipped += 1
                continue
            try:
                content = _read_attachment(
                    service,
                    message_id,
                    attachment_id,
                    settings.max_attachment_bytes,
                )
                parsed = parse_statement(bank, content)
                status = (
                    "review"
                    if parsed.transactions
                    else "needs_parser"
                )
                details = parsed.details
            except UnsupportedStatementError as error:
                status = "needs_attention"
                details = str(error)
            database.add_import(
                message_id=message_id,
                attachment_id=attachment_id,
                filename=part["filename"][:255],
                sender=sender,
                bank=bank,
                status=status,
                details=details,
            )
            staged += 1

    LOGGER.info(
        "Gmail poll completed: scanned=%d staged=%d skipped=%d",
        len(message_refs),
        staged,
        skipped,
    )
    return PollResult(
        scanned=len(message_refs), staged=staged, skipped=skipped
    )


def handle_gmail_error(error: HttpError | RefreshError) -> str:
    LOGGER.error("Gmail polling failed (%s).", type(error).__name__)
    return f"Gmail polling failed ({type(error).__name__}); check authorization and Gmail API configuration."

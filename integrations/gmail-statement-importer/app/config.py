from __future__ import annotations

import os
import re
from dataclasses import dataclass
from pathlib import Path


SENDER_PATTERN = re.compile(
    r"[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}",
    re.IGNORECASE,
)


@dataclass(frozen=True)
class Settings:
    username: str
    password: str
    encryption_key: str
    oauth_client_secrets: Path
    oauth_redirect_uri: str
    allowed_senders: frozenset[str]
    database_path: Path
    poll_interval_seconds: int
    max_attachment_bytes: int

    @classmethod
    def from_env(cls) -> Settings:
        def required(name: str) -> str:
            value = os.getenv(name, "").strip()
            if not value:
                raise ValueError(f"{name} must be set")
            return value

        def positive_int(name: str, default: int) -> int:
            value = int(os.getenv(name, str(default)))
            if value <= 0:
                raise ValueError(f"{name} must be a positive integer")
            return value

        username = required("APP_USERNAME")
        password = required("APP_PASSWORD")
        if password == "replace-with-a-long-random-password" or len(password) < 16:
            raise ValueError("APP_PASSWORD must be a unique password of at least 16 characters")

        sender_values = [
            sender.strip().lower()
            for sender in os.getenv("GMAIL_ALLOWED_SENDERS", "").split(",")
            if sender.strip()
        ]
        if any(SENDER_PATTERN.fullmatch(sender) is None for sender in sender_values):
            raise ValueError(
                "GMAIL_ALLOWED_SENDERS must contain exact email addresses separated by commas"
            )

        return cls(
            username=username,
            password=password,
            encryption_key=required("GOOGLE_TOKEN_ENCRYPTION_KEY"),
            oauth_client_secrets=Path(
                required("GOOGLE_OAUTH_CLIENT_SECRETS")
            ),
            oauth_redirect_uri=required("GOOGLE_OAUTH_REDIRECT_URI"),
            allowed_senders=frozenset(sender_values),
            database_path=Path(
                os.getenv("DATABASE_PATH", "/data/importer.sqlite3")
            ),
            poll_interval_seconds=positive_int("POLL_INTERVAL_SECONDS", 60),
            max_attachment_bytes=positive_int(
                "MAX_ATTACHMENT_BYTES", 20_000_000
            ),
        )

from __future__ import annotations

from contextlib import contextmanager
import sqlite3
from pathlib import Path
from typing import Any, Generator


class Database:
    def __init__(self, path: Path) -> None:
        self.path = path
        self.path.parent.mkdir(parents=True, exist_ok=True)
        with self.connect() as connection:
            connection.executescript(
                """
                CREATE TABLE IF NOT EXISTS oauth_tokens (
                    id INTEGER PRIMARY KEY CHECK (id = 1),
                    encrypted_json BLOB NOT NULL
                );
                CREATE TABLE IF NOT EXISTS imports (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    message_id TEXT NOT NULL,
                    attachment_id TEXT NOT NULL,
                    filename TEXT NOT NULL,
                    sender TEXT NOT NULL,
                    bank TEXT NOT NULL,
                    status TEXT NOT NULL,
                    details TEXT NOT NULL,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(message_id, attachment_id)
                );
                CREATE TABLE IF NOT EXISTS poll_state (
                    id INTEGER PRIMARY KEY CHECK (id = 1),
                    last_poll_at TEXT,
                    last_error TEXT
                );
                INSERT OR IGNORE INTO poll_state (id) VALUES (1);
                """
            )

    @contextmanager
    def connect(self) -> Generator[sqlite3.Connection, None, None]:
        connection = sqlite3.connect(self.path, timeout=10)
        connection.row_factory = sqlite3.Row
        try:
            with connection:
                yield connection
        finally:
            connection.close()

    def get_token(self) -> bytes | None:
        with self.connect() as connection:
            row = connection.execute(
                "SELECT encrypted_json FROM oauth_tokens WHERE id = 1"
            ).fetchone()
        return None if row is None else bytes(row["encrypted_json"])

    def put_token(self, encrypted_json: bytes) -> None:
        with self.connect() as connection:
            connection.execute(
                """
                INSERT INTO oauth_tokens (id, encrypted_json) VALUES (1, ?)
                ON CONFLICT(id) DO UPDATE SET encrypted_json = excluded.encrypted_json
                """,
                (encrypted_json,),
            )

    def has_import(self, message_id: str, attachment_id: str) -> bool:
        with self.connect() as connection:
            row = connection.execute(
                """
                SELECT 1 FROM imports
                WHERE message_id = ? AND attachment_id = ?
                """,
                (message_id, attachment_id),
            ).fetchone()
        return row is not None

    def add_import(
        self,
        *,
        message_id: str,
        attachment_id: str,
        filename: str,
        sender: str,
        bank: str,
        status: str,
        details: str,
    ) -> int:
        with self.connect() as connection:
            cursor = connection.execute(
                """
                INSERT INTO imports (
                    message_id, attachment_id, filename, sender, bank,
                    status, details
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
                """,
                (
                    message_id,
                    attachment_id,
                    filename,
                    sender,
                    bank,
                    status,
                    details,
                ),
            )
            return int(cursor.lastrowid)

    def list_imports(self, limit: int = 100) -> list[dict[str, Any]]:
        with self.connect() as connection:
            rows = connection.execute(
                """
                SELECT id, filename, sender, bank, status, details, created_at
                FROM imports ORDER BY id DESC LIMIT ?
                """,
                (limit,),
            ).fetchall()
        return [dict(row) for row in rows]

    def set_poll_state(self, *, last_error: str | None) -> None:
        with self.connect() as connection:
            connection.execute(
                """
                UPDATE poll_state
                SET last_poll_at = CURRENT_TIMESTAMP, last_error = ?
                WHERE id = 1
                """,
                (last_error,),
            )

    def get_poll_state(self) -> dict[str, str | None]:
        with self.connect() as connection:
            row = connection.execute(
                "SELECT last_poll_at, last_error FROM poll_state WHERE id = 1"
            ).fetchone()
        return dict(row)

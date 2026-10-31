import pytest
from cryptography.fernet import Fernet

from app.config import Settings


def _set_required_environment(monkeypatch, tmp_path) -> None:
    monkeypatch.setenv("APP_USERNAME", "local-admin")
    monkeypatch.setenv("APP_PASSWORD", "long-unique-password-123")
    monkeypatch.setenv(
        "GOOGLE_TOKEN_ENCRYPTION_KEY",
        Fernet.generate_key().decode("ascii"),
    )
    monkeypatch.setenv(
        "GOOGLE_OAUTH_CLIENT_SECRETS",
        str(tmp_path / "oauth-client.json"),
    )
    monkeypatch.setenv(
        "GOOGLE_OAUTH_REDIRECT_URI",
        "http://localhost:8082/oauth/callback",
    )
    monkeypatch.setenv("GMAIL_ALLOWED_SENDERS", "statements@example.com")
    monkeypatch.setenv("DATABASE_PATH", str(tmp_path / "importer.sqlite3"))


def test_settings_normalize_exact_sender_addresses(
    monkeypatch, tmp_path
) -> None:
    _set_required_environment(monkeypatch, tmp_path)
    monkeypatch.setenv(
        "GMAIL_ALLOWED_SENDERS",
        " statements@example.com, alerts@example.org ",
    )

    settings = Settings.from_env()

    assert settings.allowed_senders == frozenset(
        {"statements@example.com", "alerts@example.org"}
    )


def test_settings_reject_sender_search_syntax(
    monkeypatch, tmp_path
) -> None:
    _set_required_environment(monkeypatch, tmp_path)
    monkeypatch.setenv(
        "GMAIL_ALLOWED_SENDERS",
        "statements@example.com} OR from:attacker@example.net",
    )

    with pytest.raises(ValueError, match="exact email addresses"):
        Settings.from_env()


def test_settings_reject_short_password(monkeypatch, tmp_path) -> None:
    _set_required_environment(monkeypatch, tmp_path)
    monkeypatch.setenv("APP_PASSWORD", "short")

    with pytest.raises(ValueError, match="at least 16 characters"):
        Settings.from_env()

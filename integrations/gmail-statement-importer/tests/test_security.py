from cryptography.fernet import Fernet
from google.oauth2.credentials import Credentials

from app.security import TokenStore, credentials_match


def test_credentials_are_encrypted_and_can_be_restored() -> None:
    store = TokenStore(Fernet.generate_key().decode("ascii"))
    credentials = Credentials(
        token="access-secret",
        refresh_token="refresh-secret",
        token_uri="https://oauth2.googleapis.com/token",
        client_id="client-id",
        client_secret="client-secret",
        scopes=["https://www.googleapis.com/auth/gmail.readonly"],
    )

    encrypted = store.encrypt_credentials(credentials)
    restored = store.decrypt_credentials(encrypted)

    assert b"refresh-secret" not in encrypted
    assert restored.refresh_token == "refresh-secret"
    assert restored.scopes == credentials.scopes


def test_basic_auth_comparison_checks_both_values() -> None:
    assert credentials_match("user", "password", "user", "password")
    assert not credentials_match("other", "password", "user", "password")
    assert not credentials_match("user", "wrong", "user", "password")

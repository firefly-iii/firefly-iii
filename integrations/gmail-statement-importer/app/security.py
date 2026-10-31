from __future__ import annotations

import hmac
import json

from cryptography.fernet import Fernet, InvalidToken
from google.oauth2.credentials import Credentials


class TokenStore:
    def __init__(self, encryption_key: str) -> None:
        try:
            self._fernet = Fernet(encryption_key.encode("ascii"))
        except (ValueError, UnicodeEncodeError) as error:
            raise ValueError(
                "GOOGLE_TOKEN_ENCRYPTION_KEY must be a valid Fernet key"
            ) from error

    def encrypt_credentials(self, credentials: Credentials) -> bytes:
        token_data = credentials.to_json().encode("utf-8")
        return self._fernet.encrypt(token_data)

    def decrypt_credentials(self, token: bytes) -> Credentials:
        try:
            data = json.loads(self._fernet.decrypt(token))
        except (InvalidToken, json.JSONDecodeError) as error:
            raise ValueError(
                "Stored Gmail credentials cannot be decrypted; check the encryption key"
            ) from error
        return Credentials.from_authorized_user_info(data)


def credentials_match(
    supplied_username: str,
    supplied_password: str,
    expected_username: str,
    expected_password: str,
) -> bool:
    return hmac.compare_digest(
        supplied_username.encode("utf-8"), expected_username.encode("utf-8")
    ) and hmac.compare_digest(
        supplied_password.encode("utf-8"), expected_password.encode("utf-8")
    )

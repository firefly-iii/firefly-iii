from __future__ import annotations

from dataclasses import dataclass

import pymupdf


class UnsupportedStatementError(ValueError):
    pass


@dataclass(frozen=True)
class ParseResult:
    transactions: tuple[dict[str, str], ...]
    details: str


def parse_statement(bank: str, pdf_bytes: bytes) -> ParseResult:
    if not pdf_bytes:
        raise UnsupportedStatementError("The PDF attachment is empty.")
    try:
        with pymupdf.open(stream=pdf_bytes, filetype="pdf") as document:
            if document.is_encrypted:
                raise UnsupportedStatementError(
                    "The PDF is password-protected and was not processed."
                )
            if len(document) == 0:
                raise UnsupportedStatementError("The PDF contains no pages.")
            has_text = any(page.get_text().strip() for page in document)
    except (pymupdf.FileDataError, RuntimeError) as error:
        raise UnsupportedStatementError(
            "The attachment is not a readable PDF."
        ) from error

    if not has_text:
        return ParseResult(
            transactions=(),
            details=(
                f"{bank}: this PDF has no selectable text. OCR and a reviewed "
                "bank-specific parser are not configured, so no transactions "
                "were inferred."
            ),
        )
    return ParseResult(
        transactions=(),
        details=(
            f"{bank}: PDF received. A parser for this statement layout has "
            "not been configured yet; no transactions were inferred or imported."
        ),
    )

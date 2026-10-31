import pymupdf
import pytest

from app.parser import UnsupportedStatementError, parse_statement


def test_parser_never_infers_transactions_without_a_bank_template() -> None:
    document = pymupdf.open()
    page = document.new_page()
    page.insert_text((72, 72), "Statement Date Description Debit Credit Balance")
    pdf_bytes = document.tobytes()
    document.close()

    result = parse_statement("example-bank", pdf_bytes)

    assert result.transactions == ()
    assert "no transactions were inferred or imported" in result.details


def test_parser_rejects_empty_and_invalid_files() -> None:
    with pytest.raises(UnsupportedStatementError, match="empty"):
        parse_statement("example-bank", b"")
    with pytest.raises(UnsupportedStatementError, match="readable PDF"):
        parse_statement("example-bank", b"not a PDF")


def test_parser_does_not_try_to_read_encrypted_pdf() -> None:
    document = pymupdf.open()
    document.new_page()
    pdf_bytes = document.tobytes(
        encryption=pymupdf.PDF_ENCRYPT_AES_256,
        owner_pw="owner-secret",
        user_pw="user-secret",
    )
    document.close()

    with pytest.raises(UnsupportedStatementError, match="password-protected"):
        parse_statement("example-bank", pdf_bytes)

from app.security import calculate_signature, canonical_request, verify_signature


def test_canonical_request_and_signature_match_the_shared_test_vector() -> None:
    canonical = canonical_request(1_700_000_000, "post", "/v1/forms/generate", b"hello")

    assert canonical == (
        b"1700000000\nPOST\n/v1/forms/generate\n"
        b"2cf24dba5fb0a30e26e83b2ac5b9e29e1b161e5c1fa7425e73043362938b9824"
    )
    assert calculate_signature(
        "test-secret", 1_700_000_000, "post", "/v1/forms/generate", b"hello"
    ) == "0a3261878e7c7e82ba8197b3c18129598e47cb7bcafa92a91d785844463c0fbc"


def test_verification_rejects_stale_and_tampered_requests() -> None:
    timestamp = 1_700_000_000
    signature = calculate_signature(
        "test-secret", timestamp, "POST", "/v1/forms/generate", b"hello"
    )

    assert verify_signature(
        "test-secret",
        signature,
        timestamp,
        "POST",
        "/v1/forms/generate",
        b"hello",
        300,
        timestamp + 300,
    )
    assert not verify_signature(
        "test-secret",
        signature,
        timestamp,
        "POST",
        "/v1/forms/generate",
        b"tampered",
        300,
        timestamp,
    )
    assert not verify_signature(
        "test-secret",
        signature,
        timestamp,
        "POST",
        "/v1/forms/generate",
        b"hello",
        300,
        timestamp + 301,
    )

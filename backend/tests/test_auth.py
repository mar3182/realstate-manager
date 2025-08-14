from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)


def test_register_and_login_and_me():
    # Register user
    r = client.post(
        "/api/v1/auth/register",
        json={"email": "user1@example.com", "password": "secret123", "agency_name": "Acme Realty"},
    )
    assert r.status_code == 200, r.text
    data = r.json()
    assert data["email"] == "user1@example.com"
    assert data["agency_id"] is not None

    # Login (OAuth2 password form)
    r2 = client.post(
        "/api/v1/auth/login",
        data={"username": "user1@example.com", "password": "secret123"},
        headers={"Content-Type": "application/x-www-form-urlencoded"},
    )
    assert r2.status_code == 200, r2.text
    token = r2.json()["access_token"]
    assert token

    # Me endpoint
    r3 = client.get("/api/v1/auth/me", headers={"Authorization": f"Bearer {token}"})
    assert r3.status_code == 200
    me = r3.json()
    assert me["email"] == "user1@example.com"

    # Create property using token (no tenant header)
    prop_payload = {"title": "Auth Home", "description": "With token", "price": 100000, "address": "1 Way"}
    r4 = client.post("/api/v1/properties/", json=prop_payload, headers={"Authorization": f"Bearer {token}"})
    assert r4.status_code == 200, r4.text
    created = r4.json()
    assert created["title"] == "Auth Home"

    # List properties using token
    r5 = client.get("/api/v1/properties/", headers={"Authorization": f"Bearer {token}"})
    assert r5.status_code == 200
    props = r5.json()
    assert any(p["title"] == "Auth Home" for p in props)

from fastapi.testclient import TestClient
from app.main import app

client = TestClient(app)

def test_property_create_and_list_with_tenant_header():
    payload = {"title": "Test Home", "description": "Nice place", "price": 123456.78, "address": "123 Lane"}
    r = client.post("/api/v1/properties/", json=payload, headers={"X-Tenant-ID": "42"})
    assert r.status_code == 200, r.text
    data = r.json()
    assert data["title"] == "Test Home"

    r2 = client.get("/api/v1/properties/", headers={"X-Tenant-ID": "42"})
    assert r2.status_code == 200
    lst = r2.json()
    assert len(lst) == 1
    assert lst[0]["title"] == "Test Home"

def test_isolation_between_tenants():
    client.post("/api/v1/properties/", json={"title": "T1 Property"})
    client.post("/api/v1/properties/", json={"title": "T2 Property"}, headers={"X-Tenant-ID": "2"})

    r1 = client.get("/api/v1/properties/")
    r2 = client.get("/api/v1/properties/", headers={"X-Tenant-ID": "2"})
    assert len(r1.json()) == 1
    assert len(r2.json()) == 1
    assert r1.json()[0]["title"] == "T1 Property"
    assert r2.json()[0]["title"] == "T2 Property"

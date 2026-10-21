#!/usr/bin/env python3
"""Static guardrails for the separate DE3 lab. No Docker services are started."""
import json
import subprocess
from pathlib import Path

root = Path(__file__).resolve().parents[1]
result = subprocess.run(
    ["docker", "compose", "config", "--format", "json"],
    cwd=str(root), check=True, stdout=subprocess.PIPE,
    stderr=subprocess.PIPE, universal_newlines=True
)
stack = json.loads(result.stdout)
services = stack["services"]
required = {
    "mysql", "wordpress", "phpmyadmin", "nginx", "prometheus",
    "node-exporter", "cadvisor", "mysql-exporter", "nginx-exporter",
    "grafana", "loki", "promtail"
}
assert required <= services.keys(), f"Missing services: {required - services.keys()}"

# The protected, currently-running EcoShop uses OTHER Docker volumes.
names = [v.get("name", key) for key, v in stack["volumes"].items()]
assert names and all(name.startswith("de3_") for name in names), names
assert not services["mysql"].get("ports"), "Never publish MySQL to host"
assert not services["loki"].get("ports"), "Never publish Loki to host"
assert services["phpmyadmin"].get("ports"), "phpMyAdmin must be reachable"
for name in ("phpmyadmin", "grafana", "prometheus"):
    for p in services[name].get("ports", []):
        assert p.get("host_ip") in ("127.0.0.1", "::1"), (name, p)
assert services["mysql"]["networks"].keys() == {"database"}, "MySQL network isolation changed"
assert stack["networks"]["database"].get("internal") is True, "DB network must be internal"

dashboard = json.loads((root / "grafana/dashboards/ecoshop-de3.json").read_text())
assert len(dashboard["panels"]) >= 5, "Dashboard needs >=5 panels"
assert (root / "promtail/config.yml").is_file()
assert (root / "docs/logql.md").is_file()
assert (root / ".gitignore").is_file()
print("STATIC_VALIDATION_OK – isolated volumes, database, ports, services, dashboard")

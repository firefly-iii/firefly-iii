# PMOVES Integration – Firefly III Docker + GHCR

Adds GHCR publish workflow and a pmoves-net compose file so PMOVES can run the stack via external images.

Usage (local):
```bash
docker network create pmoves-net || true
docker compose -f docker-compose.pmoves-net.yml up -d
# UI: http://localhost:8080
```

Image will be published as `ghcr.io/POWERFULMOVES/pmoves-firefly-iii:main`.

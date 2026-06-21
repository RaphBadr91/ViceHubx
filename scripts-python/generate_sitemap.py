#!/usr/bin/env python3
"""
ViceHub X — Générateur de sitemap (script interne optionnel).

Scanne index.php + les fichiers de /pages et produit /sitemap.xml.
Aucune dépendance externe : Python 3 standard uniquement.

Usage :
    python3 scripts-python/generate_sitemap.py
"""
from __future__ import annotations

import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
PRIORITY = {
    "/index.php": "1.0",
    "/pages/news.php": "0.9",
    "/pages/leaks-lab.php": "0.8",
    "/pages/guides.php": "0.8",
}


def collect_routes() -> list[str]:
    routes = ["/index.php"]
    pages_dir = ROOT / "pages"
    if pages_dir.is_dir():
        for php in sorted(pages_dir.glob("*.php")):
            routes.append(f"/pages/{php.name}")
    return routes


def build_sitemap(routes: list[str]) -> str:
    today = datetime.date.today().isoformat()
    lines = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
    ]
    for route in routes:
        prio = PRIORITY.get(route, "0.6")
        lines.append(
            f"  <url><loc>{route}</loc>"
            f"<lastmod>{today}</lastmod>"
            f"<priority>{prio}</priority></url>"
        )
    lines.append("</urlset>")
    return "\n".join(lines) + "\n"


def main() -> None:
    routes = collect_routes()
    xml = build_sitemap(routes)
    out = ROOT / "sitemap.xml"
    out.write_text(xml, encoding="utf-8")
    print(f"✓ {out} régénéré ({len(routes)} URLs).")


if __name__ == "__main__":
    main()

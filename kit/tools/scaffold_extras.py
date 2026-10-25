#!/usr/bin/env python3
"""Membuat file pendukung git/CI dari SPEC.json ke root proyek.

- .gitignore            : dasar per platform + tambahan per ekosistem (stack.ecosystems)
- .github/workflows/ci.yml : setup runtime per ekosistem + perintah dari stack.commands
- .github/dependabot.yml   : per ekosistem
- .github/pull_request_template.md
- .env.example          : kerangka (lengkapi dengan variabel proyek)
- CLAUDE.md / GEMINI.md : file penunjuk ke AGENTS.md bila alat tersebut dipakai (meta.ai_tools)

Pemakaian: python kit/tools/scaffold_extras.py [--root .] [--spec SPEC.json] [--force]
Tidak menimpa file yang sudah ada kecuali --force.
"""
from __future__ import annotations

import argparse
import json
import shutil
import sys
from pathlib import Path

sys.dont_write_bytecode = True

sys.path.insert(0, str(Path(__file__).resolve().parent))
import extras_lib as X  # noqa: E402


def write(root: Path, rel: str, content: str | None, force: bool, src: Path | None = None) -> None:
    dst = root / rel
    dst.parent.mkdir(parents=True, exist_ok=True)
    if dst.exists() and not force:
        print(f"lewati (sudah ada): {rel}")
        return
    if src is not None:
        shutil.copyfile(src, dst)
    else:
        dst.write_text(content or "", encoding="utf-8")
    print(f"dibuat: {rel}")


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--root", default=".")
    ap.add_argument("--spec", default=None)
    ap.add_argument("--force", action="store_true")
    a = ap.parse_args()

    root = Path(a.root).resolve()
    spec_path = Path(a.spec).resolve() if a.spec else root / "SPEC.json"
    spec = json.loads(spec_path.read_text(encoding="utf-8"))
    platform = spec["meta"]["platform"]
    if platform not in {"web", "mobile", "desktop", "iot"}:
        print(f"Platform tidak dikenal: {platform}")
        return 1
    ecos = X.ecosystems_of(spec)
    commands = spec.get("stack", {}).get("commands") or None

    write(root, ".gitignore", X.render_gitignore(platform, ecos), a.force)
    write(root, ".github/workflows/ci.yml", X.render_ci(ecos, commands), a.force)
    write(root, ".github/dependabot.yml", X.render_dependabot(ecos), a.force)
    write(root, ".github/pull_request_template.md", None, a.force, src=X.EXTRAS / "pull_request_template.md")
    if not (root / ".env.example").exists():
        write(root, ".env.example",
              "# Daftar NAMA variabel lingkungan. Jangan isi nilai rahasia di berkas ini.\n"
              "# Salin menjadi .env lalu isi nilainya secara lokal (.env tidak di-commit).\n"
              "# Tambahkan variabel sesuai docs/ARCHITECTURE.md bagian Environments and Configuration.\n",
              False)
    for name, body in X.pointer_files(spec).items():
        write(root, name, body, a.force)
    if commands is None:
        print("PERINGATAN: stack.commands kosong; ci.yml berisi GANTI-PERINTAH dan akan gagal sampai diisi.")
    return 0


if __name__ == "__main__":
    sys.exit(main())

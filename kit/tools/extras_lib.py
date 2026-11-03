"""Pustaka bersama untuk merender file pendukung git/CI dari SPEC.json.

Dipakai oleh scaffold_extras.py (mode agen) dan make_chat_bundles.py (mode chat).
Mendukung stack bebas lewat `stack.ecosystems` (boleh lebih dari satu, mis. ["node", "rust"] untuk Tauri,
["php", "node"] untuk Laravel + Vite) dan `stack.commands` (perintah nyata proyek).
"""
from __future__ import annotations

import re
from pathlib import Path

KIT = Path(__file__).resolve().parent.parent
EXTRAS = KIT / "templates" / "extras"

ECOSYSTEMS = ["node", "python", "php", "dart-flutter", "go", "rust", "jvm", "dotnet", "platformio", "other"]

# Langkah setup runtime di GitHub Actions. Versi bertanda "verifikasi" perlu dicek tiap semester.
CI_SETUP = {
    "node": """      - uses: actions/setup-node@v4
        with:
          node-version: lts/*""",
    "python": """      - uses: actions/setup-python@v5
        with:
          python-version: "3.x\"""",
    "php": """      - uses: shivammathur/setup-php@v2
        with:
          php-version: latest  # sesuaikan dengan versi yang dipakai proyek""",
    "dart-flutter": """      - uses: subosito/flutter-action@v2
        with:
          channel: stable""",
    "go": """      - uses: actions/setup-go@v5
        with:
          go-version: stable""",
    "rust": """      - uses: dtolnay/rust-toolchain@stable
        with:
          components: rustfmt, clippy""",
    "jvm": """      - uses: actions/setup-java@v4
        with:
          distribution: temurin
          java-version: "21"  # verifikasi versi LTS yang dipakai proyek""",
    "dotnet": """      - uses: actions/setup-dotnet@v4
        with:
          dotnet-version: "10.0.x"  # verifikasi; samakan dengan global.json bila ada""",
    "platformio": """      - uses: actions/setup-python@v5
        with:
          python-version: "3.x"
      - run: pip install platformio
      - name: Siapkan secrets contoh (secrets.h tidak ada di repo)
        run: |
          if [ -f include/secrets.example.h ] && [ ! -f include/secrets.h ]; then cp include/secrets.example.h include/secrets.h; fi""",
    "other": """      # Ekosistem lain: tambahkan langkah setup runtime di sini (cari action resmi untuk bahasamu).""",
}

DEPENDABOT = {
    "node": "npm",
    "python": "pip",
    "php": "composer",
    "dart-flutter": "pub",
    "go": "gomod",
    "rust": "cargo",
    "jvm": "gradle",
    "dotnet": "nuget",
    "platformio": None,
    "other": None,
}

GITIGNORE_ECO = {
    "node": ["node_modules/", "dist/", "build/", "coverage/", "*.tsbuildinfo", "npm-debug.log*", "yarn-error.log*", ".pnpm-store/"],
    "python": ["__pycache__/", "*.py[cod]", ".venv/", "venv/", ".pytest_cache/", ".mypy_cache/", ".ruff_cache/", "*.egg-info/", ".coverage", "htmlcov/"],
    "php": ["vendor/", "storage/*.key", "storage/logs/", "bootstrap/cache/*.php", ".phpunit.result.cache", "public/hot", "public/storage"],
    "dart-flutter": [".dart_tool/", ".flutter-plugins", ".flutter-plugins-dependencies", "build/", "*.iml", "android/key.properties", "android/app/upload-keystore.jks"],
    "go": ["bin/", "*.exe", "*.test", "*.out", "vendor/"],
    "rust": ["target/"],
    "jvm": [".gradle/", "build/", "target/", "*.class", "local.properties", "*.jks", "*.keystore"],
    "dotnet": ["bin/", "obj/", "*.user", ".vs/", "TestResults/"],
    "platformio": [".pio/", ".vscode/.browse.c_cpp.db*", ".vscode/c_cpp_properties.json", ".vscode/launch.json", "secrets.h", "*.bin", "*.elf"],
    "other": [],
}

PLACEHOLDER = "GANTI-PERINTAH"

POINTER_FILES = {
    # alat -> (nama berkas, isi). Hanya untuk alat yang diketahui tidak selalu membaca AGENTS.md otomatis.
    "claude code": ("CLAUDE.md", "@AGENTS.md\n"),
    "gemini cli": ("GEMINI.md", "@AGENTS.md\n"),
}


def ecosystems_of(spec: dict) -> list[str]:
    ecos = spec.get("stack", {}).get("ecosystems") or ["other"]
    return [e for e in ecos if e in ECOSYSTEMS] or ["other"]


def _run(name: str, cmd: str | None, key: str) -> str:
    if cmd is None:
        cmd = f'echo "{PLACEHOLDER}: isi dengan stack.commands.{key} dari SPEC.json" && exit 1'
    cmd = cmd.strip()
    if "\n" in cmd:
        body = "\n".join("          " + ln for ln in cmd.splitlines())
        return f"      - name: {name}\n        run: |\n{body}"
    return f"      - name: {name}\n        run: {cmd}"


def render_ci(ecosystems: list[str], commands: dict | None) -> str:
    """commands=None menghasilkan placeholder yang sengaja gagal sampai diganti."""
    c = commands or {}
    use = (lambda k: c.get(k)) if commands is not None else (lambda k: None)
    setup = "\n".join(CI_SETUP[e] for e in ecosystems)
    steps = [_run("Instal dependensi", use("install"), "install")]
    cs = c.get("ci_setup", "") if commands is not None else ""
    if cs and cs.strip():
        steps.append(_run("Persiapan lingkungan CI", cs, "ci_setup"))
    steps.append(_run("Lint", use("lint"), "lint"))
    tc = c.get("typecheck", "") if commands is not None else ""
    if tc and tc.strip():
        steps.append(_run("Pemeriksaan tipe", tc, "typecheck"))
    steps += [
        _run("Tes", use("test"), "test"),
        _run("Build", use("build"), "build"),
    ]
    return (
        "# CI dirender dari SPEC.json (stack.ecosystems dan stack.commands).\n"
        "# Perintah harus identik dengan docs/EVALS.md, docs/TESTING.md, dan AGENTS.md.\n"
        "# Periksa versi action terbaru tiap semester; untuk produksi pin action ke SHA commit.\n"
        "name: CI\n\n"
        "on:\n  pull_request:\n  push:\n    branches: [main]\n\n"
        "permissions:\n  contents: read\n\n"
        "jobs:\n  checks:\n    runs-on: ubuntu-latest\n    steps:\n"
        "      - uses: actions/checkout@v4\n"
        f"{setup}\n" + "\n".join(steps) + "\n"
    )


def render_gitignore(platform: str, ecosystems: list[str]) -> str:
    base = (EXTRAS / f"gitignore.{platform}").read_text(encoding="utf-8").rstrip() + "\n"
    have = {ln.strip() for ln in base.splitlines()}
    extra: list[str] = []
    for e in ecosystems:
        for pat in GITIGNORE_ECO[e]:
            if pat not in have and pat not in extra:
                extra.append(pat)
    if extra:
        base += "\n# Tambahan untuk ekosistem: " + ", ".join(ecosystems) + "\n" + "\n".join(extra) + "\n"
    return base


def render_dependabot(ecosystems: list[str]) -> str:
    out = [
        "# Dirender dari stack.ecosystems di SPEC.json. Sesuaikan 'directory' bila manifest ada di subfolder.",
        "version: 2",
        "updates:",
        '  - package-ecosystem: "github-actions"',
        '    directory: "/"',
        "    schedule:",
        '      interval: "weekly"',
    ]
    skipped = []
    for e in ecosystems:
        pe = DEPENDABOT[e]
        if pe is None:
            skipped.append(e)
            continue
        out += [
            f'  - package-ecosystem: "{pe}"',
            '    directory: "/"',
            "    schedule:",
            '      interval: "weekly"',
            "    open-pull-requests-limit: 5",
        ]
    if skipped:
        out.insert(1, f"# Tidak didukung Dependabot ({', '.join(skipped)}): periksa versi dependensi secara manual.")
    return "\n".join(out) + "\n"


def _norm_tool(name: str) -> str:
    return re.sub(r"[^a-z0-9]+", " ", str(name).lower()).strip()


def pointer_files(spec: dict) -> dict[str, str]:
    files = {}
    for tool in spec.get("meta", {}).get("ai_tools", []):
        key = _norm_tool(tool)
        if key in POINTER_FILES:
            name, body = POINTER_FILES[key]
            files[name] = body
    return files

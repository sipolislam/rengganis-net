#!/usr/bin/env python3
"""Uji mandiri kit: memastikan template, manifest, dan validator konsisten.

Yang diuji (mekanisme, bukan kualitas isi buatan model):
  1. Template: slot sintaks benar, referensi {{spec:...}} valid, heading tidak mengandung slot.
  2. Paket sintetis lengkap (contoh CatatKas + pengisi otomatis) harus lulus validator tanpa FAIL.
  3. Mutasi: tiap kerusakan yang disengaja harus ditangkap validator dengan kode FAIL yang tepat.

Jalankan: python tools/selftest.py
"""
from __future__ import annotations

import copy
import json
import re
import shutil
import subprocess
import sys
import tempfile
from pathlib import Path

sys.dont_write_bytecode = True

KIT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(KIT / "tools"))
import validate  # noqa: E402

MANIFEST = json.loads((KIT / "templates" / "_manifest.json").read_text(encoding="utf-8"))
EXAMPLE = KIT / "examples" / "catatkas"
SLOT_RE = re.compile(r"\{\{(.*?)\}\}", re.S)


def get_path(spec: dict, dotted: str):
    cur = spec
    for part in dotted.split("."):
        if isinstance(cur, dict) and part in cur:
            cur = cur[part]
        else:
            raise KeyError(dotted)
    return cur


# ------------------------------------------------------------ 1. template checks

def check_templates(spec: dict) -> list[str]:
    errs = []
    all_t = [f["template"] for f in MANIFEST["files"]] + [c["template"] for c in MANIFEST["conditional"]]
    for rel in all_t:
        p = KIT / "templates" / rel
        if not p.is_file():
            errs.append(f"template hilang: {rel}")
            continue
        text = p.read_text(encoding="utf-8")
        if text.count("{{") != text.count("}}"):
            errs.append(f"{rel}: jumlah '{{{{' dan '}}}}' tidak sama")
        for m in SLOT_RE.finditer(text):
            body = m.group(1).strip()
            if "\n" in m.group(1):
                errs.append(f"{rel}: slot multi-baris: {body[:40]}")
            if body.startswith("spec:"):
                ref = body[5:]
                other_platform = ref.startswith("platform_specific.") and ref.split(".")[1] != spec["meta"]["platform"]
                if not other_platform:
                    try:
                        get_path(spec, ref)
                    except KeyError:
                        errs.append(f"{rel}: referensi spec tidak ada: {body}")
            elif body.startswith("WRITE:"):
                if len(body) < 40:
                    errs.append(f"{rel}: instruksi WRITE terlalu pendek: {body}")
            else:
                errs.append(f"{rel}: slot tak dikenal: {body[:40]}")
        for ln in text.splitlines():
            if ln.startswith("## ") and "{{" in ln:
                errs.append(f"{rel}: heading tingkat 2 tidak boleh berisi slot: {ln}")
        if not text.lstrip().startswith("# "):
            errs.append(f"{rel}: harus diawali judul '# '")
    return errs


# ------------------------------------------------------------ 2. synthetic package

def filler(spec: dict, instruction: str) -> str:
    app = spec["meta"]["app_name"]
    ids = ", ".join(f["id"] for f in spec["features"])
    rcs = spec["student_reflection"]["risk_controls"]
    ids += ", " + ", ".join(f"R{i + 1}" for i in range(len(rcs)))
    kws = ", ".join(spec["stack"]["keywords"])
    if "Mermaid" in instruction:
        return "flowchart LR\n  A[Klien] --> B[Server]\n  B --> C[(Database)]"
    if "kolom persis: ID, Judul" in instruction:  # tabel task TASKS.md
        rows = ["| ID | Judul | Fitur | Ukuran | Status | Branch | Bergantung pada | Kriteria selesai |",
                "|---|---|---|---|---|---|---|---|",
                "| T-001 | Setup repositori | setup | M | todo | chore/T-001-setup | - | Perintah proyek berjalan |"]
        n = 2
        rows[-1] = rows[-1].replace("| setup |", "| setup, " + ", ".join(f"R{i + 1}" for i in range(len(spec["student_reflection"]["risk_controls"]))) + " |")
        for f in spec["features"]:
            for part in ("dasar", "lanjutan"):
                rows.append(f"| T-{n:03d} | {f['name']} {part} | {f['id']} | M | todo | feat/T-{n:03d} | T-001 | Acceptance {f['id']} terpenuhi |")
                n += 1
        return "\n".join(rows)
    if "student_reflection.biggest_risk" in instruction:
        rc = " ".join(f"R{i + 1}: {x}" for i, x in enumerate(spec["student_reflection"]["risk_controls"]))
        return f'Risiko menurut mahasiswa: "{spec["student_reflection"]["biggest_risk"]}". {rc} ' + filler(spec, "lainnya")
    if "spec.meta.assistant_proposed (tulis jalurnya persis" in instruction:
        return "Usulan asisten: " + (", ".join(spec["meta"]["assistant_proposed"]) or "tidak ada") + ". " + filler(spec, "lainnya")
    extra = ""
    if spec["meta"]["platform"] == "iot" and spec["platform_specific"].get("iot", {}).get("mains_voltage"):
        extra = " Peringatan tegangan jaringan (220 V): hanya komponen berisolasi dan supervisi teknisi."
    if spec["stack"].get("source") != "recommended":
        extra += f' Stack dipilih mahasiswa karena: "{spec["stack"]["custom_reason"]}".'
    for p in spec["meta"].get("secondary_platforms", []):
        extra += f" Platform pendukung: {p}."
    return (
        f"Bagian ini menjelaskan {app} secara spesifik untuk fitur {ids} memakai stack {kws}. "
        "Isinya berupa langkah konkret yang dapat diperiksa, dengan contoh nyata dari entitas data proyek, "
        "batas yang jelas, dan rujukan ke dokumen lain agar agen tidak perlu menebak. "
        "Setiap butir mengikuti spesifikasi yang sudah dikonfirmasi mahasiswa." + extra
    )


def render_template(spec: dict, rel: str) -> str:
    text = (KIT / "templates" / rel).read_text(encoding="utf-8")

    def sub(m: re.Match) -> str:
        body = m.group(1).strip()
        if body.startswith("spec:"):
            v = get_path(spec, body[5:])
            return str(v)
        return filler(spec, body)

    return SLOT_RE.sub(sub, text)


def build_package(root: Path, spec: dict, use_examples: bool = True) -> None:
    (root / "docs").mkdir(parents=True, exist_ok=True)
    (root / "SPEC.json").write_text(json.dumps(spec, ensure_ascii=False, indent=2), encoding="utf-8")
    files = validate.expected_files(MANIFEST, spec)
    for f in files:
        out = root / f["output"]
        out.parent.mkdir(parents=True, exist_ok=True)
        ex = EXAMPLE / f["output"]
        if use_examples and ex.is_file():
            shutil.copyfile(ex, out)
        else:
            out.write_text(render_template(spec, f["template"]), encoding="utf-8")
    subprocess.run([sys.executable, str(KIT / "tools" / "scaffold_extras.py"), "--root", str(root)],
                   check=True, capture_output=True)


def run_validate(root: Path) -> validate.Report:
    return validate.run(root, KIT, root / "SPEC.json")


def fails(rep: validate.Report) -> set[str]:
    return {c for s, c, _ in rep.items if s == "FAIL"}


def warns(rep: validate.Report) -> set[str]:
    return {c for s, c, _ in rep.items if s == "WARN"}


# ------------------------------------------------------------ 3. mutations

def iot_spec(base: dict) -> dict:
    s = copy.deepcopy(base)
    s["meta"]["platform"] = "iot"
    s["meta"]["level"] = "loop"
    s["platform_specific"] = {"iot": {
        "board": "ESP32 DevKit", "sensors": ["DHT22"], "actuators": ["relay berisolasi"],
        "connectivity": "Wi-Fi", "protocol": "MQTT dengan TLS", "broker": "Mosquitto",
        "power": "adaptor USB", "ota": False, "simulation": "Wokwi",
        "mains_voltage": True, "edge_ai": "none",
        "failsafe": "Saat sensor error relay dimatikan; saat Wi-Fi putus otomasi tetap berjalan di ESP32; saat boot relay mulai mati dengan jeda 3 menit.",
        "offline_alert": True}}
    s["meta"]["assistant_proposed"] = ["meta.app_name", "metrics"]
    return s


def custom_spec(base: dict) -> dict:
    """Stack pilihan mahasiswa: Flutter + Laravel API, dengan panel admin web sebagai platform pendukung."""
    s = copy.deepcopy(base)
    s["meta"]["platform"] = "mobile"
    s["meta"]["secondary_platforms"] = ["web"]
    s["meta"]["starting_point"] = "existing"
    s["meta"]["ai_tools"] = ["Claude Code", "Gemini CLI"]
    s["platform_specific"] = {"mobile": {
        "framework": "Flutter", "target_os": "android", "min_android": "default Flutter",
        "offline": "cache", "native_features": ["kamera"], "distribution": "APK"}}
    s["stack"].update({
        "frontend": "Flutter (Dart)", "backend": "Laravel REST API", "database": "MySQL",
        "auth": "Laravel Sanctum", "hosting": "VPS kampus", "testing": "flutter test dan PHPUnit",
        "keywords": ["Flutter", "Dart", "Laravel", "MySQL"],
        "source": "custom", "custom_reason": "Tim sudah memakai Flutter dan Laravel di tempat magang",
        "custom_layers": [], "ecosystems": ["dart-flutter", "php"],
        "commands": {"install": "flutter pub get && composer install --working-dir=api",
                     "dev": "flutter run", "lint": "flutter analyze", "typecheck": "",
                     "test": "flutter test", "build": "flutter build apk --debug"},
        "versions": []})
    return s


def edit(path: Path, fn) -> None:
    path.write_text(fn(path.read_text(encoding="utf-8")), encoding="utf-8")


def main() -> int:
    base = json.loads((EXAMPLE / "SPEC.json").read_text(encoding="utf-8"))
    problems: list[str] = []
    results: list[tuple[str, bool, str]] = []

    def record(name: str, ok: bool, detail: str = "") -> None:
        results.append((name, ok, detail))
        if not ok:
            problems.append(f"{name}: {detail}")

    # 1
    terrs = check_templates(base)
    record("template: sintaks dan referensi spec", not terrs, "; ".join(terrs[:5]))
    terrs_iot = check_templates(iot_spec(base))
    record("template: referensi spec (varian iot)", not terrs_iot, "; ".join(terrs_iot[:5]))

    # 2 baseline web
    with tempfile.TemporaryDirectory() as tmp:
        root = Path(tmp)
        build_package(root, base)
        rep = run_validate(root)
        record("paket web sintetis lulus tanpa FAIL", not fails(rep), str(sorted(fails(rep))) + " " + "; ".join(m for s, c, m in rep.items if s == "FAIL")[:300])

    # baseline iot + loop
    ispec = iot_spec(base)
    with tempfile.TemporaryDirectory() as tmp:
        root = Path(tmp)
        build_package(root, ispec, use_examples=True)
        rep = run_validate(root)
        record("paket iot+loop sintetis lulus tanpa FAIL", not fails(rep), "; ".join(m for s, c, m in rep.items if s == "FAIL")[:300])
        record("iot: HARDWARE.md dan LOOP.md diwajibkan", (root / "docs/HARDWARE.md").is_file() and (root / "docs/LOOP.md").is_file())

    # baseline stack pilihan mahasiswa (Flutter + Laravel, platform pendukung web)
    cspec = custom_spec(base)
    with tempfile.TemporaryDirectory() as tmp:
        root = Path(tmp)
        build_package(root, cspec, use_examples=False)
        rep = run_validate(root)
        record("paket stack custom (Flutter+Laravel) lulus tanpa FAIL", not fails(rep),
               "; ".join(m for s, c, m in rep.items if s == "FAIL")[:300])
        ci = (root / ".github/workflows/ci.yml").read_text(encoding="utf-8")
        dep = (root / ".github/dependabot.yml").read_text(encoding="utf-8")
        gi = (root / ".gitignore").read_text(encoding="utf-8")
        record("custom: CI memakai setup Flutter + PHP dan perintah dari SPEC",
               "flutter-action" in ci and "setup-php" in ci and "flutter analyze" in ci and "setup-node" not in ci)
        record("custom: dependabot pub + composer, gitignore .dart_tool + vendor",
               '"pub"' in dep and '"composer"' in dep and ".dart_tool/" in gi and "vendor/" in gi)
        record("custom: file penunjuk CLAUDE.md dan GEMINI.md dibuat",
               (root / "CLAUDE.md").read_text().strip() == "@AGENTS.md" and (root / "GEMINI.md").is_file())

    # mutasi web
    def mutate(name: str, expect: str, fn, spec: dict | None = None, kind: str = "FAIL") -> None:
        spec = spec or base
        with tempfile.TemporaryDirectory() as tmp:
            root = Path(tmp)
            build_package(root, spec, use_examples=spec is base)
            fn(root)
            rep = run_validate(root)
            got = fails(rep) if kind == "FAIL" else warns(rep)
            record(f"mutasi: {name} -> {kind} {expect}", expect in got,
                   f"kode {kind} yang didapat: {sorted(got)}")

    mutate("hapus GUARDRAILS.md", "FILES", lambda r: (r / "docs/GUARDRAILS.md").unlink())
    mutate("slot tersisa di REVIEW.md", "SLOT", lambda r: edit(r / "docs/REVIEW.md", lambda t: t + "\n{{WRITE: sisa}}\n"))
    mutate("hapus heading Quality Gates", "HEADINGS", lambda r: edit(r / "docs/EVALS.md", lambda t: t.replace("## Quality Gates", "## Gerbang")))
    mutate("ID F3 hilang dari EVALS", "FEATURES", lambda r: edit(r / "docs/EVALS.md", lambda t: re.sub(r"\bF3\b", "F-3", t)))
    mutate("status task tidak valid", "TASKS", lambda r: edit(r / "docs/TASKS.md", lambda t: t.replace("| todo |", "| pending |", 1)))
    mutate(".env tidak di-gitignore", "GIT", lambda r: edit(r / ".gitignore", lambda t: re.sub(r"^\.env\s*$", "", t, flags=re.M).replace(".env.*", "").replace(".env*", "")))
    mutate("ARCHITECTURE terlalu pendek", "LENGTH", lambda r: (r / "docs/ARCHITECTURE.md").write_text(
        (r / "docs/ARCHITECTURE.md").read_text(encoding="utf-8")[:200], encoding="utf-8"))
    mutate("rahasia di README", "SECRET", lambda r: edit(r / "README.md", lambda t: t + "\nsk-abcdefghijklmnopqrstuvwxyz123456\n"))
    mutate("kutipan risiko mahasiswa diubah", "REFLECTION", lambda r: edit(r / "docs/PRD.md", lambda t: t.replace("Saya khawatir saldo salah hitung", "Risiko utama adalah waktu")))
    mutate("baris OWASP A05 dihapus", "OWASP", lambda r: edit(r / "docs/SECURITY.md", lambda t: "\n".join(ln for ln in t.splitlines() if not ln.startswith("| A05:2025")) + "\n"))
    mutate("diagram Mermaid dihapus", "DIAGRAM", lambda r: edit(r / "docs/ARCHITECTURE.md", lambda t: re.sub(r"```mermaid.*?```", "", t, flags=re.S)))
    mutate("kata kunci stack hilang dari AGENTS", "STACK", lambda r: edit(r / "AGENTS.md", lambda t: t.replace("Supabase", "Penyedia")))
    mutate("nama aplikasi hilang dari README", "APPNAME", lambda r: edit(r / "README.md", lambda t: t.replace("CatatKas", "Aplikasi")))
    mutate("AGENTS.md terlalu panjang", "AGENTS", lambda r: edit(r / "AGENTS.md", lambda t: t + "\nbaris\n" * 120))
    mutate("ADR kurang", "ADR", lambda r: edit(r / "docs/DECISIONS.md", lambda t: t.replace("### ADR-", "### Catatan-")))
    mutate("teks pengisi TBD", "FILLER", lambda r: edit(r / "docs/LESSONS.md", lambda t: t + "\nTBD\n"))

    bad = copy.deepcopy(base)
    bad["features"][1]["id"] = "F9"
    mutate("ID fitur tidak berurutan", "SPEC", lambda r: None, spec=bad)
    bad2 = copy.deepcopy(base)
    bad2["student_reflection"]["biggest_risk"] = ""
    mutate("refleksi mahasiswa kosong", "SPEC", lambda r: None, spec=bad2)

    mutate("istilah usang menghasilkan WARN", "STALE",
           lambda r: edit(r / "docs/CODE_STYLE.md", lambda t: t + "\nGunakan create-react-app untuk memulai.\n"), kind="WARN")

    # mutasi stack bebas dan perintah
    mutate("perintah test hilang dari TESTING", "COMMANDS",
           lambda r: edit(r / "docs/TESTING.md", lambda t: t.replace("npm test", "npm run unit")))
    mutate("ci.yml masih placeholder", "CI",
           lambda r: edit(r / ".github/workflows/ci.yml", lambda t: t + "      - run: echo GANTI-PERINTAH\n"))
    mutate("ci.yml tidak menjalankan build -> WARN", "CI",
           lambda r: edit(r / ".github/workflows/ci.yml", lambda t: t.replace("run: npm run build", "run: echo skip")), kind="WARN")
    mutate("custom: alasan mahasiswa tidak dikutip di DECISIONS", "CUSTOM",
           lambda r: edit(r / "docs/DECISIONS.md", lambda t: t.replace("Tim sudah memakai Flutter", "Dipilih")), spec=cspec)
    mutate("custom: platform pendukung tidak dibahas", "SECONDARY",
           lambda r: edit(r / "docs/ARCHITECTURE.md", lambda t: re.sub(r"(?i)\bweb\b", "situs", t)), spec=cspec)
    bad3 = copy.deepcopy(cspec)
    bad3["stack"]["custom_reason"] = ""
    mutate("custom tanpa alasan mahasiswa", "SPEC", lambda r: None, spec=bad3)
    bad4 = copy.deepcopy(base)
    bad4["stack"]["ecosystems"] = ["cobol"]
    mutate("ekosistem tidak dikenal", "SPEC", lambda r: None, spec=bad4)
    bad5 = copy.deepcopy(base)
    bad5["stack"]["commands"]["lint"] = ""
    mutate("perintah lint kosong", "SPEC", lambda r: None, spec=bad5)
    bad6 = copy.deepcopy(base)
    bad6["meta"]["secondary_platforms"] = ["web"]
    mutate("platform pendukung sama dengan utama", "SPEC", lambda r: None, spec=bad6)

    # mutasi v1.2
    mutate("rujukan ADR yang tidak ada", "REFS",
           lambda r: edit(r / "docs/ARCHITECTURE.md", lambda t: t + "\nLihat ADR-000.\n"))
    mutate("rujukan task yang tidak ada", "REFS",
           lambda r: edit(r / "docs/WORKFLOW.md", lambda t: t + "\nKerjakan T-999.\n"))
    mutate("R1 hilang dari EVALS", "RISK",
           lambda r: edit(r / "docs/EVALS.md", lambda t: re.sub(r"\bR1\b", "Rx", t)))
    mutate("tanggal ADR placeholder", "FILLER",
           lambda r: edit(r / "docs/DECISIONS.md", lambda t: t + "\nTanggal: tanggal diisi saat sesi pertama\n"))
    mutate("iot: failsafe tidak dikutip di HARDWARE", "FAILSAFE",
           lambda r: edit(r / "docs/HARDWARE.md", lambda t: t.replace("Saat sensor error relay dimatikan", "Lihat SPEC")), spec=ispec)
    mutate("iot: usulan asisten tidak dicatat", "PROPOSED",
           lambda r: edit(r / "docs/DECISIONS.md", lambda t: t.replace("meta.app_name", "nama")), spec=ispec)
    def drop_failsafe(r: Path) -> None:
        s = json.loads((r / "SPEC.json").read_text(encoding="utf-8"))
        del s["platform_specific"]["iot"]["failsafe"]
        (r / "SPEC.json").write_text(json.dumps(s), encoding="utf-8")
    mutate("iot tanpa failsafe di SPEC", "SPEC", drop_failsafe, spec=ispec)
    bad8 = copy.deepcopy(base)
    bad8["student_reflection"]["risk_controls"] = []
    mutate("tanpa kendali risiko", "SPEC", lambda r: None, spec=bad8)
    with tempfile.TemporaryDirectory() as tmp:
        root = Path(tmp)
        build_package(root, base)
        edit(root / "docs/CODE_STYLE.md", lambda t: t + "\nContoh Blade aman: `{{ $item->nama }}` (escape otomatis).\n")
        rep_b = run_validate(root)
        record("sintaks Blade {{ }} tidak dianggap slot", "SLOT" not in fails(rep_b), str(sorted(fails(rep_b))))

    # mutasi iot
    mutate("iot: peringatan 220 V dihapus dari GUARDRAILS", "SAFETY",
           lambda r: edit(r / "docs/GUARDRAILS.md", lambda t: re.sub(r"(?i)(220\s*V|tegangan jaringan|mains)", "xx", t)), spec=ispec)

    # laporan
    width = max(len(n) for n, _, _ in results)
    for name, ok, detail in results:
        print(f"[{'OK ' if ok else 'GAGAL'}] {name.ljust(width)}" + (f"  {detail}" if not ok and detail else ""))
    print(f"\n{sum(1 for _, ok, _ in results if ok)}/{len(results)} uji lolos")
    return 1 if problems else 0


if __name__ == "__main__":
    sys.exit(main())

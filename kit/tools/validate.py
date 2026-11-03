#!/usr/bin/env python3
"""Validator paket dokumen proyek (hanya pustaka standar Python, tanpa dependensi).

Pemakaian (dari root proyek mahasiswa, tempat folder kit/ berada):
    python kit/tools/validate.py
    python kit/tools/validate.py --root . --spec SPEC.json --strict

Kode keluar: 0 bila tidak ada FAIL, 1 bila ada FAIL (atau WARN dengan --strict).
Validator memeriksa struktur dan konsistensi, bukan kebenaran isi teknis; itu tugas review manusia.
"""
from __future__ import annotations

import argparse
import sys as _sys
_sys.dont_write_bytecode = True  # jangan membuat __pycache__ di dalam kit/
import json
import re
import sys
from pathlib import Path

KIT_DEFAULT = Path(__file__).resolve().parent.parent

PLATFORMS = {"web", "mobile", "desktop", "iot"}
LEVELS = {"dasar", "menengah", "loop"}
LANGS = {"id", "en"}
PRIORITIES = {"must", "should", "could"}
PERSONAL = {"none", "basic", "location_contact", "sensitive"}
AI_MODES = {"none", "api", "on_device"}
EDGE_AI = {"none", "on_device", "cloud"}
STACK_SOURCES = {"recommended", "custom", "mixed"}
STARTING = {"new", "existing"}
ECOSYSTEMS = {"node", "python", "php", "dart-flutter", "go", "rust", "jvm", "dotnet", "platformio", "other"}
REQUIRED_COMMANDS = ("install", "lint", "test", "build")
CI_PLACEHOLDER = "GANTI-PERINTAH"
OFFLINE = {"none", "cache", "offline-first"}
TARGET_OS = {"android", "ios", "both"}

PLATFORM_REQUIRED = {
    "web": {"rendering": str, "i18n": bool, "seo": bool},
    "mobile": {"framework": str, "target_os": str, "min_android": (str, int, float),
               "offline": str, "native_features": list, "distribution": str},
    "desktop": {"framework": str, "os_targets": list, "hardware_access": list,
                "updater": bool, "distribution": str},
    "iot": {"board": str, "sensors": list, "actuators": list, "connectivity": str,
            "protocol": str, "broker": str, "power": str, "ota": bool,
            "simulation": str, "mains_voltage": bool, "edge_ai": str},
}

SECRET_PATTERNS = [
    (r"sk-[A-Za-z0-9_\-]{20,}", "kunci API bergaya sk-"),
    (r"AKIA[0-9A-Z]{16}", "kunci akses AWS"),
    (r"ghp_[A-Za-z0-9]{30,}", "token GitHub"),
    (r"xox[baprs]-[A-Za-z0-9-]{10,}", "token Slack"),
    (r"-----BEGIN [A-Z ]*PRIVATE KEY-----", "kunci privat"),
    (r"AIza[0-9A-Za-z_\-]{30,}", "kunci API Google"),
]


class Report:
    def __init__(self) -> None:
        self.items: list[tuple[str, str, str]] = []

    def add(self, status: str, code: str, msg: str) -> None:
        self.items.append((status, code, msg))

    def ok(self, code: str, msg: str) -> None:
        self.add("PASS", code, msg)

    def warn(self, code: str, msg: str) -> None:
        self.add("WARN", code, msg)

    def fail(self, code: str, msg: str) -> None:
        self.add("FAIL", code, msg)

    def count(self, status: str) -> int:
        return sum(1 for s, _, _ in self.items if s == status)


# ---------------------------------------------------------------- util

def words(text: str) -> int:
    return len(re.findall(r"\w+", text, flags=re.UNICODE))


def norm(text: str) -> str:
    text = text.lower()
    text = re.sub(r"[\"'“”‘’`*_>]", "", text)
    return re.sub(r"\s+", " ", text).strip()


def nonempty_str(v) -> bool:
    return isinstance(v, str) and v.strip() != ""


def str_list(v, minimum: int = 0) -> bool:
    return isinstance(v, list) and len(v) >= minimum and all(nonempty_str(x) for x in v)


def template_headings(kit: Path, template_rel: str) -> list[str]:
    text = (kit / "templates" / template_rel).read_text(encoding="utf-8")
    return [ln.strip() for ln in text.splitlines() if re.match(r"^## \S", ln)]


def doc_headings(text: str) -> set[str]:
    return {norm(ln.strip()) for ln in text.splitlines() if re.match(r"^## \S", ln)}


def parse_table_rows(text: str) -> list[list[str]]:
    rows = []
    for ln in text.splitlines():
        ln = ln.strip()
        if ln.startswith("|") and ln.endswith("|"):
            cells = [c.strip() for c in ln.strip("|").split("|")]
            rows.append(cells)
    return rows


# ---------------------------------------------------------------- spec

def check_spec(spec: dict, rep: Report) -> None:
    errs: list[str] = []

    def need(cond: bool, msg: str) -> None:
        if not cond:
            errs.append(msg)

    for key in ["meta", "problem", "goals", "non_goals", "users", "features", "metrics", "stack",
                "data", "security", "ai_in_app", "design", "platform_specific", "risks",
                "student_reflection"]:
        need(key in spec, f"kunci '{key}' tidak ada")
    if errs:
        for e in errs:
            rep.fail("SPEC", e)
        return

    m = spec["meta"]
    need(nonempty_str(m.get("app_name")) and 2 <= len(m["app_name"]) <= 40, "meta.app_name kosong atau di luar 2-40 karakter")
    need(isinstance(m.get("slug"), str) and re.fullmatch(r"[a-z0-9]+(-[a-z0-9]+)*", m["slug"] or ""), "meta.slug harus kebab-case")
    need(nonempty_str(m.get("one_liner")) and len(m["one_liner"]) <= 140, "meta.one_liner kosong atau > 140 karakter")
    need(m.get("platform") in PLATFORMS, f"meta.platform harus salah satu dari {sorted(PLATFORMS)}")
    need(m.get("level") in LEVELS, f"meta.level harus salah satu dari {sorted(LEVELS)}")
    need(m.get("language") in LANGS, "meta.language harus id atau en")
    need(isinstance(m.get("ai_tools"), list), "meta.ai_tools harus berupa daftar")
    need(isinstance(m.get("team_size"), int) and m["team_size"] >= 1, "meta.team_size harus bilangan bulat >= 1")
    need(isinstance(m.get("duration_weeks"), int) and m["duration_weeks"] >= 1, "meta.duration_weeks harus bilangan bulat >= 1")
    need(isinstance(m.get("delegated_questions"), list), "meta.delegated_questions harus berupa daftar")
    sec_p = m.get("secondary_platforms")
    need(isinstance(sec_p, list) and all(x in PLATFORMS for x in sec_p) and m.get("platform") not in (sec_p or []),
         f"meta.secondary_platforms harus daftar dari {sorted(PLATFORMS)} dan tidak memuat platform utama")
    need(m.get("starting_point") in STARTING, "meta.starting_point harus new atau existing")
    need(isinstance(m.get("created_date"), str) and re.fullmatch(r"\d{4}-\d{2}-\d{2}", m.get("created_date") or ""),
         "meta.created_date harus tanggal YYYY-MM-DD")
    need(isinstance(m.get("assistant_proposed"), list) and all(isinstance(x, str) for x in m.get("assistant_proposed") or []),
         "meta.assistant_proposed harus daftar jalur bidang (boleh kosong)")

    p = spec["problem"]
    for k in ("statement", "who", "current_alternative"):
        need(nonempty_str(p.get(k)), f"problem.{k} kosong")

    need(str_list(spec["goals"], 2), "goals minimal 2 butir tidak kosong")
    need(str_list(spec["non_goals"], 1), "non_goals minimal 1 butir tidak kosong")
    users = spec["users"]
    need(isinstance(users, list) and len(users) >= 1, "users minimal 1")
    if isinstance(users, list):
        for i, u in enumerate(users):
            for k in ("persona", "need", "quote"):
                need(isinstance(u, dict) and nonempty_str(u.get(k)), f"users[{i}].{k} kosong")
    need(isinstance(spec.get("user_conditions", []), list), "user_conditions harus daftar")

    feats = spec["features"]
    must_count = 0
    if not isinstance(feats, list) or len(feats) < 3:
        errs.append("features minimal 3 butir")
    else:
        seen = set()
        for i, f in enumerate(feats):
            exp = f"F{i + 1}"
            if not isinstance(f, dict):
                errs.append(f"features[{i}] bukan objek")
                continue
            need(f.get("id") == exp, f"features[{i}].id harus '{exp}' (berurutan)")
            need(f.get("id") not in seen, f"features[{i}].id duplikat")
            seen.add(f.get("id"))
            need(nonempty_str(f.get("name")), f"{exp}.name kosong")
            need(nonempty_str(f.get("description")), f"{exp}.description kosong")
            need(f.get("priority") in PRIORITIES, f"{exp}.priority harus must/should/could")
            need(str_list(f.get("acceptance"), 2), f"{exp}.acceptance minimal 2 butir tidak kosong")
            if f.get("priority") == "must":
                must_count += 1
        need(must_count >= 1, "minimal 1 fitur berprioritas must")

    mets = spec["metrics"]
    need(isinstance(mets, list) and len(mets) >= 2, "metrics minimal 2")
    if isinstance(mets, list):
        for i, x in enumerate(mets):
            for k in ("name", "target", "how_measured"):
                need(isinstance(x, dict) and nonempty_str(x.get(k)), f"metrics[{i}].{k} kosong")

    st = spec["stack"]
    for k in ("frontend", "backend", "database", "auth", "hosting", "testing"):
        need(nonempty_str(st.get(k)), f"stack.{k} kosong")
    need(str_list(st.get("keywords"), 3), "stack.keywords minimal 3 kata kunci tidak kosong")
    need(isinstance(st.get("extra", []), list), "stack.extra harus daftar")
    need(st.get("source") in STACK_SOURCES, "stack.source harus recommended/custom/mixed")
    if st.get("source") in ("custom", "mixed"):
        need(nonempty_str(st.get("custom_reason")), "stack.custom_reason wajib diisi alasan mahasiswa bila stack custom/mixed")
    if st.get("source") == "mixed":
        need(str_list(st.get("custom_layers"), 1), "stack.custom_layers wajib berisi lapisan yang dipilih sendiri bila mixed")
    need(isinstance(st.get("custom_layers", []), list), "stack.custom_layers harus daftar")
    ecos = st.get("ecosystems")
    need(isinstance(ecos, list) and len(ecos) >= 1 and all(e in ECOSYSTEMS for e in ecos),
         f"stack.ecosystems minimal 1, dari {sorted(ECOSYSTEMS)}")
    cmds = st.get("commands")
    if not isinstance(cmds, dict):
        errs.append("stack.commands harus objek dengan install, lint, test, build")
    else:
        for k in REQUIRED_COMMANDS:
            need(nonempty_str(cmds.get(k)), f"stack.commands.{k} kosong (perintah nyata proyek)")
        for k in ("dev", "typecheck", "ci_setup"):
            need(isinstance(cmds.get(k, ""), str), f"stack.commands.{k} harus string (boleh kosong)")
    need(isinstance(st.get("versions", []), list), "stack.versions harus daftar")

    d = spec["data"]
    ents = d.get("entities")
    need(isinstance(ents, list) and len(ents) >= 1, "data.entities minimal 1")
    if isinstance(ents, list):
        for i, e in enumerate(ents):
            need(isinstance(e, dict) and nonempty_str(e.get("name")) and str_list(e.get("fields"), 1),
                 f"data.entities[{i}] harus punya name dan fields (>= 1)")
    need(d.get("personal_data") in PERSONAL, f"data.personal_data harus salah satu dari {sorted(PERSONAL)}")
    need(isinstance(d.get("sensitive_notes", ""), str), "data.sensitive_notes harus string")
    if d.get("personal_data") in ("location_contact", "sensitive"):
        need(nonempty_str(d.get("sensitive_notes")), "data.sensitive_notes wajib diisi bila data lokasi/kontak atau sensitif")

    sec = spec["security"]
    need(str_list(sec.get("roles"), 1), "security.roles minimal 1")
    need(nonempty_str(sec.get("auth_method")), "security.auth_method kosong")
    need(nonempty_str(sec.get("secrets_handling")), "security.secrets_handling kosong")

    ai = spec["ai_in_app"]
    need(ai.get("mode") in AI_MODES, "ai_in_app.mode harus none/api/on_device")
    if ai.get("mode") in ("api", "on_device"):
        need(nonempty_str(ai.get("purpose")), "ai_in_app.purpose wajib bila mode bukan none")

    ds = spec["design"]
    need(nonempty_str(ds.get("tone")), "design.tone kosong")
    need(isinstance(ds.get("primary_color"), str) and re.fullmatch(r"#[0-9A-Fa-f]{6}", ds["primary_color"] or ""), "design.primary_color harus hex #RRGGBB")
    need(isinstance(ds.get("dark_mode"), bool), "design.dark_mode harus boolean")
    need(isinstance(ds.get("font", ""), str), "design.font harus string")

    plat = m.get("platform")
    ps = spec["platform_specific"]
    if plat in PLATFORM_REQUIRED:
        block = ps.get(plat) if isinstance(ps, dict) else None
        if not isinstance(block, dict):
            errs.append(f"platform_specific.{plat} tidak ada")
        else:
            for k, typ in PLATFORM_REQUIRED[plat].items():
                if k not in block:
                    errs.append(f"platform_specific.{plat}.{k} tidak ada")
                    continue
                if not isinstance(block[k], typ) or (typ is not bool and isinstance(block[k], bool) and bool not in (typ if isinstance(typ, tuple) else (typ,))):
                    errs.append(f"platform_specific.{plat}.{k} bertipe salah")
                elif isinstance(block[k], str) and not block[k].strip():
                    errs.append(f"platform_specific.{plat}.{k} kosong")
            if plat == "mobile":
                need(block.get("target_os") in TARGET_OS, "mobile.target_os harus android/ios/both")
                need(block.get("offline") in OFFLINE, "mobile.offline harus none/cache/offline-first")
            if plat == "iot":
                need(block.get("edge_ai") in EDGE_AI, "iot.edge_ai harus none/on_device/cloud")
                need(nonempty_str(block.get("failsafe")), "iot.failsafe wajib (perilaku saat sensor error, jaringan putus, boot; I-15)")
                need(isinstance(block.get("offline_alert"), bool), "iot.offline_alert harus boolean")
                need(isinstance(block.get("sensors"), list) and len(block["sensors"]) + len(block.get("actuators", [])) >= 1,
                     "iot membutuhkan minimal satu sensor atau aktuator")

    need(str_list(spec["risks"], 2), "risks minimal 2 butir tidak kosong")
    sr = spec["student_reflection"]
    need(nonempty_str(sr.get("biggest_risk")), "student_reflection.biggest_risk kosong (harus kata-kata mahasiswa)")
    need(nonempty_str(sr.get("why_this_project")), "student_reflection.why_this_project kosong")
    need(str_list(sr.get("risk_controls"), 1), "student_reflection.risk_controls minimal 1 aturan yang disetujui mahasiswa (C-14b)")

    if must_count > 5:
        rep.warn("SPEC", f"{must_count} fitur must; saran maksimal 5 agar proyek selesai")
    if errs:
        for e in errs:
            rep.fail("SPEC", e)
    else:
        rep.ok("SPEC", "SPEC.json valid secara struktur")


# ---------------------------------------------------------------- docs

def expected_files(manifest: dict, spec: dict) -> list[dict]:
    out = list(manifest["files"])
    for c in manifest.get("conditional", []):
        ok = all(spec.get("meta", {}).get(k) == v for k, v in c["when"].items())
        if ok:
            out.append(c)
    return out


def check_docs(root: Path, kit: Path, manifest: dict, spec: dict, rep: Report) -> dict[str, str]:
    texts: dict[str, str] = {}
    files = expected_files(manifest, spec)

    missing = []
    for f in files:
        path = root / f["output"]
        if not path.is_file():
            missing.append(f["output"])
        else:
            texts[f["output"]] = path.read_text(encoding="utf-8", errors="replace")
    if missing:
        rep.fail("FILES", f"{len(missing)} file belum ada: " + ", ".join(missing))
    else:
        rep.ok("FILES", f"Semua {len(files)} file dokumen ada")

    filler = [x.lower() for x in manifest.get("filler_patterns", [])]
    stale = manifest.get("stale_patterns", [])

    for f in files:
        out = f["output"]
        text = texts.get(out)
        if text is None:
            continue
        # judul
        first = next((ln for ln in text.splitlines() if ln.strip()), "")
        if not first.startswith("# "):
            rep.fail("TITLE", f"{out}: baris pertama harus judul tingkat 1 ('# ...')")
        # slot tersisa
        left = re.findall(r"\{\{\s*(?:WRITE:|spec:).*?(?:\}\}|$)", text, flags=re.S | re.M)
        if left:
            rep.fail("SLOT", f"{out}: ada slot template yang belum diisi ({len(left)}), contoh: {left[0][:60]}")
        # pengisi
        low = text.lower()
        hit = [p for p in filler if p in low]
        if hit:
            rep.fail("FILLER", f"{out}: teks pengisi ditemukan: {', '.join(hit)}")
        # heading
        required = template_headings(kit, f["template"])
        have = doc_headings(text)
        miss = [h for h in required if norm(h) not in have]
        if miss:
            rep.fail("HEADINGS", f"{out}: heading wajib hilang atau berubah: {'; '.join(miss)}")
        # panjang
        wc = words(text)
        if wc < f["min_words"]:
            rep.fail("LENGTH", f"{out}: {wc} kata (minimal {f['min_words']}); terlalu tipis atau generik")
        # kadaluarsa
        stale_hit = [s for s in stale if s.lower() in low]
        if stale_hit:
            rep.warn("STALE", f"{out}: istilah mungkin usang: {', '.join(stale_hit)} (lihat tech-baseline.md)")
        # rahasia
        for pat, label in SECRET_PATTERNS:
            if re.search(pat, text):
                rep.fail("SECRET", f"{out}: terlihat seperti {label}; hapus dan putar kuncinya")

    if not any(i[0] == "FAIL" and i[1] in ("SLOT", "HEADINGS", "LENGTH", "TITLE", "FILLER", "SECRET") for i in rep.items):
        rep.ok("DOCS", "Heading wajib, panjang minimum, dan slot template terpenuhi di semua dokumen")
    return texts


def check_consistency(texts: dict[str, str], manifest: dict, spec: dict, rep: Report) -> None:
    feats = [f["id"] for f in spec.get("features", []) if isinstance(f, dict) and "id" in f]
    bad = False
    for rel in manifest["feature_id_files"]:
        t = texts.get(rel)
        if t is None:
            continue
        miss = [fid for fid in feats if not re.search(rf"\b{re.escape(fid)}\b", t)]
        if miss:
            bad = True
            rep.fail("FEATURES", f"{rel}: ID fitur tidak muncul: {', '.join(miss)}")
    if not bad:
        rep.ok("FEATURES", f"Semua ID fitur ({len(feats)}) muncul di PRD, TASKS, dan EVALS")

    app = spec["meta"]["app_name"]
    bad = False
    for rel in manifest["app_name_files"]:
        t = texts.get(rel)
        if t is not None and app.lower() not in t.lower():
            bad = True
            rep.fail("APPNAME", f"{rel}: nama aplikasi '{app}' tidak muncul")
    if not bad:
        rep.ok("APPNAME", "Nama aplikasi konsisten di README, PRD, AGENTS")

    kws = spec["stack"]["keywords"]
    bad = False
    for rel in manifest["stack_keyword_files"]:
        t = texts.get(rel)
        if t is None:
            continue
        miss = [k for k in kws if k.lower() not in t.lower()]
        if miss:
            bad = True
            rep.fail("STACK", f"{rel}: kata kunci stack tidak muncul: {', '.join(miss)}")
    if not bad:
        rep.ok("STACK", "Kata kunci stack muncul di ARCHITECTURE dan AGENTS")

    # perintah satu sumber
    cmds = spec["stack"]["commands"]
    bad = False
    # install bukan gerbang kualitas, jadi tidak diwajibkan di EVALS
    where = {"AGENTS.md": REQUIRED_COMMANDS, "docs/TESTING.md": REQUIRED_COMMANDS,
             "docs/EVALS.md": ("lint", "test", "build")}
    for rel, keys in where.items():
        t = texts.get(rel)
        if t is None:
            continue
        miss = [k for k in keys if cmds[k].strip() not in t]
        if miss:
            bad = True
            rep.fail("COMMANDS", f"{rel}: perintah dari stack.commands tidak muncul persis: {', '.join(miss)}")
    if not bad:
        rep.ok("COMMANDS", "Perintah dari stack.commands identik di AGENTS, TESTING, EVALS")

    # stack pilihan mahasiswa
    st = spec["stack"]
    dec = texts.get("docs/DECISIONS.md")
    if st["source"] != "recommended" and dec is not None:
        probe = norm(st["custom_reason"])[:40]
        if probe and probe in norm(dec):
            rep.ok("CUSTOM", "Alasan stack pilihan mahasiswa dikutip di DECISIONS")
        else:
            rep.fail("CUSTOM", "docs/DECISIONS.md harus mengutip stack.custom_reason (stack dipilih mahasiswa)")

    # platform pendukung
    arch_t = texts.get("docs/ARCHITECTURE.md")
    sec_p = spec["meta"].get("secondary_platforms", [])
    if sec_p and arch_t is not None:
        miss = [p for p in sec_p if not re.search(rf"\b{p}\b", arch_t, flags=re.I)]
        if miss:
            rep.fail("SECONDARY", f"docs/ARCHITECTURE.md tidak membahas platform pendukung: {', '.join(miss)}")
        else:
            rep.ok("SECONDARY", "Platform pendukung dibahas di ARCHITECTURE")

    # kendali risiko R1..Rn
    rcs = spec["student_reflection"]["risk_controls"]
    rids = [f"R{i + 1}" for i in range(len(rcs))]
    bad = False
    for rel in ("docs/PRD.md", "docs/EVALS.md", "docs/TASKS.md"):
        t = texts.get(rel)
        if t is None:
            continue
        miss = [r for r in rids if not re.search(rf"\b{r}\b", t)]
        if miss:
            bad = True
            rep.fail("RISK", f"{rel}: kendali risiko tidak muncul: {', '.join(miss)}")
    prd_t = texts.get("docs/PRD.md")
    if prd_t is not None:
        for rid, rc in zip(rids, rcs):
            if norm(rc)[:40] not in norm(prd_t):
                bad = True
                rep.fail("RISK", f"docs/PRD.md: teks {rid} tidak dikutip persis dari risk_controls")
    if not bad:
        rep.ok("RISK", f"Kendali risiko ({', '.join(rids)}) ada di PRD, EVALS, TASKS")

    # rujukan ADR-nnn dan T-nnn harus ada
    dec_t = texts.get("docs/DECISIONS.md", "")
    tasks_t = texts.get("docs/TASKS.md", "")
    adrs = set(re.findall(r"^###\s+(ADR-\d+)", dec_t, flags=re.M))
    tids = set(re.findall(r"^\|\s*(T-\d{3})\s*\|", tasks_t, flags=re.M))
    broken, future = [], []
    max_adr = max((int(a[4:]) for a in adrs), default=0)
    for rel, t in texts.items():
        for ref in sorted(set(re.findall(r"\bADR-\d{3}\b", t))):
            if adrs and ref not in adrs:
                # nomor di atas ADR terakhir biasanya contoh penomoran ADR berikutnya
                (future if int(ref[4:]) > max_adr else broken).append(f"{rel}->{ref}")
        for ref in sorted(set(re.findall(r"\bT-\d{3}\b", t))):
            if tids and ref not in tids:
                broken.append(f"{rel}->{ref}")
    if future:
        rep.warn("REFS", "Rujukan ke ADR yang belum ada (pastikan hanya contoh penomoran): " + ", ".join(future[:8]))
    if broken:
        rep.fail("REFS", "Rujukan ke ADR/task yang tidak ada: " + ", ".join(broken[:8]))
    elif not future:
        rep.ok("REFS", "Semua rujukan ADR-nnn dan T-nnn menunjuk ke entri yang ada")

    # usulan asisten tercatat
    props = spec["meta"].get("assistant_proposed", [])
    if props and dec_t:
        miss = [p for p in props if p not in dec_t]
        if miss:
            rep.fail("PROPOSED", f"docs/DECISIONS.md tidak menyebut usulan asisten: {', '.join(miss)}")
        else:
            rep.ok("PROPOSED", f"{len(props)} usulan asisten tercatat di DECISIONS")

    # IoT: perilaku gagal dikutip di HARDWARE
    iot = spec.get("platform_specific", {}).get("iot")
    if spec["meta"]["platform"] == "iot" and isinstance(iot, dict) and "docs/HARDWARE.md" in texts:
        if norm(iot.get("failsafe", ""))[:40] in norm(texts["docs/HARDWARE.md"]):
            rep.ok("FAILSAFE", "Perilaku saat gagal (I-15) dikutip di HARDWARE")
        else:
            rep.fail("FAILSAFE", "docs/HARDWARE.md harus mengutip platform_specific.iot.failsafe")

    # refleksi mahasiswa dikutip di PRD
    prd = texts.get("docs/PRD.md")
    if prd is not None:
        quote = norm(spec["student_reflection"]["biggest_risk"])
        probe = quote[:40]
        if probe and probe in norm(prd):
            rep.ok("REFLECTION", "Risiko menurut mahasiswa dikutip di PRD")
        else:
            rep.fail("REFLECTION", "docs/PRD.md harus mengutip persis student_reflection.biggest_risk")

    # AGENTS.md ringkas
    ag = texts.get("AGENTS.md")
    if ag is not None:
        n = len(ag.splitlines())
        if n > manifest["agents_max_lines_fail"]:
            rep.fail("AGENTS", f"AGENTS.md {n} baris; terlalu panjang (maks {manifest['agents_max_lines_fail']}). Pindahkan detail ke docs/")
        elif n > manifest["agents_max_lines_warn"]:
            rep.warn("AGENTS", f"AGENTS.md {n} baris; sarankan <= {manifest['agents_max_lines_warn']} agar konteks agen hemat")
        else:
            rep.ok("AGENTS", f"AGENTS.md ringkas ({n} baris)")

    # Mermaid
    arch = texts.get("docs/ARCHITECTURE.md")
    if arch is not None:
        m = re.search(r"```mermaid\s*\n(.*?)```", arch, flags=re.S)
        if m and len([ln for ln in m.group(1).splitlines() if ln.strip()]) >= 3:
            rep.ok("DIAGRAM", "Diagram Mermaid ada di ARCHITECTURE")
        else:
            rep.fail("DIAGRAM", "docs/ARCHITECTURE.md harus memuat blok ```mermaid dengan minimal 3 baris isi")

    # TASKS
    tasks = texts.get("docs/TASKS.md")
    if tasks is not None:
        rows = parse_table_rows(tasks)
        header = next((r for r in rows if "ID" in r and "Status" in r), None)
        if not header:
            rep.fail("TASKS", "docs/TASKS.md: tabel tugas dengan kolom ID dan Status tidak ditemukan")
        else:
            si = header.index("Status")
            data = [r for r in rows if r and re.fullmatch(r"T-\d{3}", r[0])]
            problems = []
            ids = [r[0] for r in data]
            if len(set(ids)) != len(ids):
                problems.append("ID task duplikat")
            for r in data:
                if len(r) != len(header):
                    problems.append(f"{r[0]}: jumlah kolom {len(r)} != {len(header)}")
                elif r[si] not in manifest["task_statuses"]:
                    problems.append(f"{r[0]}: status '{r[si]}' tidak valid")
            if len(data) < manifest["min_tasks"]:
                problems.append(f"hanya {len(data)} task (minimal {manifest['min_tasks']})")
            if "T-001" not in ids:
                problems.append("T-001 tidak ada")
            if problems:
                rep.fail("TASKS", "docs/TASKS.md: " + "; ".join(problems[:6]))
            else:
                rep.ok("TASKS", f"{len(data)} task valid dengan status benar")

    # ADR
    dec = texts.get("docs/DECISIONS.md")
    if dec is not None:
        n = len(re.findall(r"^###\s+ADR-\d+", dec, flags=re.M))
        if n >= manifest["min_adrs"]:
            rep.ok("ADR", f"{n} ADR tercatat")
        else:
            rep.fail("ADR", f"docs/DECISIONS.md hanya {n} ADR (minimal {manifest['min_adrs']})")

    # OWASP
    sec = texts.get("docs/SECURITY.md")
    if sec is not None:
        rows = parse_table_rows(sec)
        got = {}
        for r in rows:
            mm = re.match(r"(A\d{2}):2025", r[0]) if r else None
            if mm and len(r) >= 3:
                got[mm.group(1)] = r[2]
        miss = [c for c in manifest["owasp_codes"] if c not in got]
        thin = [c for c, v in got.items() if words(v) < 6]
        if miss:
            rep.fail("OWASP", f"docs/SECURITY.md: baris OWASP hilang: {', '.join(miss)}")
        elif thin:
            rep.fail("OWASP", f"docs/SECURITY.md: penerapan terlalu singkat (< 6 kata) untuk: {', '.join(sorted(thin))}")
        else:
            rep.ok("OWASP", "Checklist OWASP Top 10:2025 lengkap dan terisi")

    # IoT mains
    ps = spec.get("platform_specific", {}).get("iot", {})
    if spec.get("meta", {}).get("platform") == "iot" and ps.get("mains_voltage") is True:
        g = texts.get("docs/GUARDRAILS.md", "")
        hw = texts.get("docs/HARDWARE.md", "")
        pat = re.compile(r"(220\s*V|230\s*V|tegangan jaringan|mains)", re.I)
        if pat.search(g) and pat.search(hw):
            rep.ok("SAFETY", "Peringatan tegangan jaringan ada di GUARDRAILS dan HARDWARE")
        else:
            rep.fail("SAFETY", "mains_voltage = true: peringatan tegangan jaringan (220 V) wajib ada di GUARDRAILS.md dan HARDWARE.md")


def check_ci(root: Path, spec: dict, rep: Report) -> None:
    ci = root / ".github/workflows/ci.yml"
    if not ci.is_file():
        return
    text = ci.read_text(encoding="utf-8", errors="replace")
    if CI_PLACEHOLDER in text:
        rep.fail("CI", f"ci.yml masih berisi {CI_PLACEHOLDER}; ganti dengan perintah dari stack.commands")
        return
    cmds = spec["stack"]["commands"]
    keys = list(REQUIRED_COMMANDS) + (["ci_setup"] if (cmds.get("ci_setup") or "").strip() else [])
    miss = [k for k in keys if cmds[k].strip() not in text]
    if miss:
        rep.warn("CI", f"ci.yml tidak menjalankan perintah: {', '.join(miss)} (jalankan ulang scaffold_extras.py --force atau sesuaikan)")
    else:
        rep.ok("CI", "ci.yml menjalankan perintah yang sama dengan stack.commands")


def check_git(root: Path, rep: Report) -> None:
    gi = root / ".gitignore"
    if not gi.is_file():
        rep.fail("GIT", ".gitignore tidak ada (jalankan kit/tools/scaffold_extras.py)")
    else:
        lines = {ln.strip() for ln in gi.read_text(encoding="utf-8", errors="replace").splitlines()}
        if ".env" in lines or ".env*" in lines or ".env.*" in lines:
            rep.ok("GIT", ".gitignore mengecualikan .env")
        else:
            rep.fail("GIT", ".gitignore harus memuat .env")
    for rel in (".github/workflows/ci.yml", ".github/pull_request_template.md"):
        if (root / rel).is_file():
            rep.ok("GIT", f"{rel} ada")
        else:
            rep.fail("GIT", f"{rel} tidak ada")
    if not (root / ".env.example").is_file():
        rep.warn("GIT", ".env.example belum ada (daftar nama variabel lingkungan tanpa nilai)")
    else:
        text = (root / ".env.example").read_text(encoding="utf-8", errors="replace")
        for pat, label in SECRET_PATTERNS:
            if re.search(pat, text):
                rep.fail("SECRET", f".env.example: terlihat seperti {label}")


def run(root: Path, kit: Path, spec_path: Path) -> Report:
    rep = Report()
    manifest = json.loads((kit / "templates" / "_manifest.json").read_text(encoding="utf-8"))
    if not spec_path.is_file():
        rep.fail("SPEC", f"{spec_path} tidak ditemukan")
        return rep
    try:
        spec = json.loads(spec_path.read_text(encoding="utf-8"))
    except json.JSONDecodeError as e:
        rep.fail("SPEC", f"SPEC.json bukan JSON valid: {e}")
        return rep
    check_spec(spec, rep)
    if any(s == "FAIL" and c == "SPEC" for s, c, _ in rep.items):
        rep.warn("SKIP", "Pemeriksaan dokumen dilewati sampai SPEC.json valid")
        return rep
    texts = check_docs(root, kit, manifest, spec, rep)
    if any(s == "FAIL" and c == "FILES" for s, c, _ in rep.items):
        rep.warn("SKIP", "Pemeriksaan konsistensi antar dokumen dilewati sampai semua file ada")
    else:
        check_consistency(texts, manifest, spec, rep)
    check_git(root, rep)
    check_ci(root, spec, rep)
    return rep


def main(argv: list[str] | None = None) -> int:
    ap = argparse.ArgumentParser(description="Validasi paket dokumen proyek")
    ap.add_argument("--root", default=".", help="root proyek mahasiswa (default: .)")
    ap.add_argument("--kit", default=str(KIT_DEFAULT), help="folder kit")
    ap.add_argument("--spec", default=None, help="jalur SPEC.json (default: <root>/SPEC.json)")
    ap.add_argument("--strict", action="store_true", help="WARN dianggap gagal")
    ap.add_argument("--json", action="store_true", help="keluaran JSON")
    args = ap.parse_args(argv)
    try:  # konsol Windows lama (cp1252) gagal mencetak karakter non-ASCII dari SPEC
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    except (AttributeError, ValueError):
        pass

    root = Path(args.root).resolve()
    kit = Path(args.kit).resolve()
    spec_path = Path(args.spec).resolve() if args.spec else root / "SPEC.json"
    rep = run(root, kit, spec_path)

    if args.json:
        print(json.dumps([{"status": s, "check": c, "message": m} for s, c, m in rep.items], ensure_ascii=False, indent=2))
    else:
        order = {"FAIL": 0, "WARN": 1, "PASS": 2}
        for s, c, m in sorted(rep.items, key=lambda x: order[x[0]]):
            print(f"[{s}] {c}: {m}")
        print(f"\nHASIL: {rep.count('FAIL')} FAIL, {rep.count('WARN')} WARN, {rep.count('PASS')} PASS")
    bad = rep.count("FAIL") > 0 or (args.strict and rep.count("WARN") > 0)
    return 1 if bad else 0


if __name__ == "__main__":
    sys.exit(main())

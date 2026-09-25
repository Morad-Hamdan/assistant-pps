```
╔══════════════════════════════════════════════════════════════════════╗
║                    AI HR ASSISTANT — ARCHITECTURE                   ║
╚══════════════════════════════════════════════════════════════════════╝

┌─────────────────────────────────────────────────────────────────────┐
│  FRONTEND (Dark Cyberpunk)          script.js                       │
│  ┌──────────────┐  ┌─────────────────────────────────────────────┐ │
│  │ Sidebar +    │  │ Chat panel (full page)                       │ │
│  │ 7 suggestions│  │ sendMessage() → POST /ask {question,history} │ │
│  │ guide text   │  │ addMessage(): **bold**, `code`, \n→<br>    │ │
│  └──────────────┘  │ chatHistory[] slice(-10), typing indicator   │ │
│                    │ Suggestions: Resume / Critique / Emp.        │ │
│                    │   suspendus / PPS suspendus / Prevu (15j) /  │ │
│                    │   Requalification / Apercu PPS               │ │
│                    └─────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
                                │ POST /ask
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  BACKEND FastAPI              main.py → routers → /ask endpoint    │
│                               schemas: AskRequest{question,history}│
│                               config: Ollama URL, model, SQLite    │
└─────────────────────────────────────────────────────────────────────┘
                                │ ask_hr_ai(question, history)
                                ▼
┌─────────────────────────────────────────────────────────────────────┐
│  1. detect_intent()  ───▶  intent + params                         │
│  2. build_employee_dataset() ▶  dataset[154]  (SQLite)            │
│  3. ROUTE:                                                         │
│                                                                     │
│  DIRECT FORMAT (no LLM):                                            │
│    dataset_summary  → stats card with totals                       │
│    pps_summary      → stats card PPS totals                       │
│    pps_lookup       → analyse d'un PPS specifique                 │
│    employee_lookup / matricule_lookup                              │
│                     → _employee_card()                             │
│    high_risk / employee_suspended                                  │
│     / requalification / prevu / suspended                          │
│                     → _build_table()                                │
│                                                                     │
│  LLM PATH (general):                                                │
│    Try name match → employee_lookup if found                       │
│    Build prompt with history(6) + context(risk+PPS+names)          │
│    ask_ollama() ▶ timeout 120s, keep_alive 5m                      │
└─────────────────────────────────────────────────────────────────────┘
                                │
              ┌─────────────────┴──────────────────┐
              ▼                                    ▼
┌─────────────────────────┐  ┌───────────────────────────────────────┐
│ OLLAMA SERVICE          │  │ DATABASE (SQLite)                     │
│ ollama_service.py       │  │ database.py                           │
│ POST /api/generate      │  │ employees + pps_assignments tables    │
│ timeout 120s            │  │ build_employee_dataset():             │
│ keep_alive 5m           │  │  JOIN + statut aggregation            │
│ fallback only           │  │ get_dataset_meta(): counts, dist      │
│                         │  │                                       │
│                         │  │ PPS LIFECYCLE:                        │
│                         │  │  EC value    → EN COURS     [>]      │
│                         │  │  diff > 3yr  → SUSPENDU    [X]       │
│                         │  │  >= 2.9589   → PREVU       [~]       │
│                         │  │  >= 2.75     → REQUALIFIER [!]       │
│                         │  │  else        → VALIDE      [OK]      │
│                         │  │  no date     → NON APPLICABLE        │
│                         │  │                                       │
│                         │  │ Risk: suspended→HIGH, req/prevu→MED   │
│                         │  │ Scores: strict%, operational%         │
│                         │  │ normalize_text(): NFKD→ASCII→lower    │
│                         │  │                                       │
│                         │  │ DATA FLOW:                            │
│                         │  │  recap.xlsx ─(migrate)─▶ hr_database.db│
│                         │  │  refresh_sqlite.bat pour re-sync      │
└─────────────────────────┘  └───────────────────────────────────────┘


═══════════════════════════════════════════════════════════════════════
                         INTENTS (keyword-based)
═══════════════════════════════════════════════════════════════════════

 dataset_summary     │ "resume", "résumé", "resume des donnees"
 high_risk           │ "critique"
 employee_suspended  │ "employes suspendus"
 suspended           │ "suspendus" (+ PPS)
 prevu               │ "prevu"
 requalification     │ "requalification"
 pps_summary         │ "pps" + no other match
 pps_lookup          │ "PPS 009", "PPS 042" (regex \d{3})
 employee_lookup     │ ALL-CAPS words (skip list)
 matricule_lookup    │ \d{4,6} regex
 greeting            │ "bonjour", "salut", "hello"...
 general             │ fallback → LLM (Ollama)


═══════════════════════════════════════════════════════════════════════
                    4 RESPONSE FORMATS (unified)
═══════════════════════════════════════════════════════════════════════

 TABLEAU ```     │ employee_suspended, high_risk, requalification,
                 │ suspended(PPS), prevu

 FICHE EMPLOYE   │ employee_lookup, matricule_lookup

 STATS CARD      │ dataset_summary, pps_summary

 ANALYSE PPS     │ pps_lookup

 Unified icons:  [OK] valides  [!] requalifier  [~] prevus  [>] en cours  [X] suspendus


═══════════════════════════════════════════════════════════════════════
                    DATA LAYER
═══════════════════════════════════════════════════════════════════════

 SOURCE:     backend/data/recap.xlsx  (154 employees, 28 PPS columns)
 RUNTIME:    backend/data/hr_database.db  (SQLite, populated via migration)
 MIGRATION:  backend/scripts/migrate_excel_to_sqlite.py
 SYNC:       backend/data/refresh_sqlite.bat  (double-clic after manual Excel edit)

 Tables:
   employees         id, matricule, nom, prenom, processus, uap, eap
   pps_assignments   id, employee_id, pps_name, date_debut_validite, statut, raw_value

 4312 assignments total across 6 statuses:
   514 valides, 15 a_requalifier, 0 prevus, 38 suspendus, 80 en_cours, 3665 non_applicable


═══════════════════════════════════════════════════════════════════════
                    ROADMAP — WHAT HAS BEEN BUILT
═══════════════════════════════════════════════════════════════════════

FRONTEND
───────
• Chat panel with full-page messaging, sidebar + suggestions bar
• Welcome message + 7 suggestion buttons in "phrases completes" style
• Search guide text: matricule (81041), PPS+numéro (PPS 009), nom MAJ
• sendMessage() → POST /ask with question + last 10 history messages
• addMessage() with markdown-like rendering (**bold**, `code`, line breaks)
• Typing indicator (3 animated dots) during API call
• Auto-resizing textarea input, Enter key to send
• Error handling, auto-scroll, timestamps (HH:MM French format)
• Glassmorphism cards, dark theme, cyan/violet accents

BACKEND — CORE & ROUTES
───────
• FastAPI app with CORS
• Router: POST /ask → AskRequest → AskResponse
• Routes: GET /health, GET /api/employee/meta, GET /api/employee/analyze
• Pydantic schemas: Message, AskRequest, AskResponse
• Config: Ollama URL, model, Excel path, SQLite path

BACKEND — INTENT ENGINE
───────
• Keyword-based intent detection with confidence levels (0.40-0.95)
• Minimal synonym sets (pruned from larger lists)
• Priority: PPS → risk → suspended → prevu → dataset → matricule → caps → general
• Skip list for common all-caps words

BACKEND — AI SERVICE
───────
• ask_hr_ai(): main orchestration
• 4 unified response formats: table, card, stats, analysis
• _build_table(): priority chain suspended > requalify > prevu > en_cours > valid
• _employee_card(): detailed per-employee view with all statuses
• LLM prompt builder for general intent

BACKEND — DATABASE (SQLite)
───────
• SQLite schema: employees + pps_assignments with indexes
• build_employee_dataset(): full aggregation per employee
• get_dataset_meta(): metadata + statut distribution
• Employee/Matricule/PPS lookup queries
• "en_cours" status for EC values (80 detected)

BACKEND — OLLAMA SERVICE
───────
• ask_ollama(prompt) → POST /api/generate to local Ollama
• Timeout: 120s, keep_alive: 5m
• Error handling with French messages

MIGRATION
───────
• migrate_excel_to_sqlite.py: reads recap.xlsx, detects EC → en_cours
• refresh_sqlite.bat: one-click re-sync after manual Excel edits
• Clears old data before re-insertion (idempotent)

CLEANUP
───────
• Dead code removed: test_agent.py (LangGraph), generate_report.py, Rapport_PFE.*
• requirements.txt cleaned: removed langgraph, langchain-core (unused)
• All __pycache__ directories purged


DATA FLOW
───────
  Question → detect_intent() → build_employee_dataset() (SQLite)
    → router (direct format / LLM prompt)
    → ask_ollama() (if general)
    → JSON response {intent, answer, data, error?}
    → frontend renders in chat panel

  Excel edit → refresh_sqlite.bat → migrate script → hr_database.db updated
```

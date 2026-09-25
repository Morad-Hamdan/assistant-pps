import re

CONFIDENCE_RULES = {
    "exact_phrase": 0.95,
    "synonym_keyword": 0.85,
    "regex_match": 0.80,
    "caps_detection": 0.75,
    "partial_context": 0.70,
    "name_fallback": 0.60,
    "general_fallback": 0.40,
}


def detect_intent(question: str) -> dict:
    q = question.lower().strip()

    # PPS-related questions (check FIRST)
    if "pps" in q:
        if any(w in q for w in ["prevu"]):
            return {
                "intent": "prevu",
                "confidence": CONFIDENCE_RULES["exact_phrase"],
                "reason": "pps prevu keywords"
            }
        if any(w in q for w in ["suspendus"]):
            return {
                "intent": "suspended",
                "confidence": CONFIDENCE_RULES["exact_phrase"],
                "reason": "pps suspendu keywords"
            }
        pps_num = re.search(r'\bPPS\s+(\d{3})\b', question, re.I)
        if pps_num:
            return {
                "intent": "pps_lookup",
                "pps_query": f"PPS {pps_num.group(1)}",
                "confidence": CONFIDENCE_RULES["exact_phrase"],
                "reason": f"specific PPS: PPS {pps_num.group(1)}"
            }
        caps_pps = re.findall(r'\b[A-Z]{2,}(?:\s+[A-Z]{2,})*\b', question)
        pps_skip = {"HIGH", "RISK", "LOW", "MEDIUM", "STATUS", "STATUT", "PPS", "CHECK", "HELLO", "HI", "BONJOUR", "SALUT"}
        filtered = [w for w in caps_pps if w not in pps_skip]
        if filtered:
            return {
                "intent": "employee_lookup",
                "employee_name": " ".join(filtered).lower().strip(),
                "confidence": CONFIDENCE_RULES["caps_detection"],
                "reason": "pps + caps name"
            }
        return {
            "intent": "pps_summary",
            "confidence": CONFIDENCE_RULES["synonym_keyword"],
            "reason": "pps keyword only"
        }

    # Haut risque / High Risk
    if any(w in q for w in ["critique"]):
        return {
            "intent": "high_risk",
            "confidence": CONFIDENCE_RULES["synonym_keyword"],
            "reason": "matched synonym"
        }

    # Requalification
    if any(w in q for w in ["requalification"]):
        return {
            "intent": "requalification",
            "confidence": CONFIDENCE_RULES["exact_phrase"],
            "reason": "matched exact phrase"
        }

    # Employee suspended (compact list, not PPS-centric)
    if any(k in q for k in ["employes suspendus"]):
        return {
            "intent": "employee_suspended",
            "confidence": CONFIDENCE_RULES["exact_phrase"],
            "reason": "employee suspended keywords"
        }

    # Suspendu / Suspended
    if any(w in q for w in ["suspendus"]):
        return {
            "intent": "suspended",
            "confidence": CONFIDENCE_RULES["exact_phrase"],
            "reason": "matched exact phrase"
        }

    # Prévu (PPS qui expirent dans 15 jours)
    if any(w in q for w in ["prevu"]):
        return {
            "intent": "prevu",
            "confidence": CONFIDENCE_RULES["exact_phrase"],
            "reason": "matched exact phrase"
        }

    # Résumé / Dataset summary
    if any(w in q for w in ["resume", "résumé", "resumé", "resume des donnees", "résumé des données"]):
        return {
            "intent": "dataset_summary",
            "confidence": CONFIDENCE_RULES["exact_phrase"],
            "reason": "matched exact phrase"
        }

    # Matricule lookup
    matricule_match = re.search(r'\b(\d{4,6})\b', q)
    if matricule_match:
        matricule = matricule_match.group(1)
        return {
            "intent": "matricule_lookup",
            "matricule": matricule,
            "confidence": CONFIDENCE_RULES["regex_match"],
            "reason": f"detected matricule number {matricule}"
        }

    # Greeting detection (before general fallback)
    greetings = {"bonjour", "salut", "hello", "salam", "bonsoir", "coucou", "hey", "hi"}
    q_words = set(q.split())
    if q_words & greetings or q.strip() in greetings:
        return {
            "intent": "greeting",
            "confidence": CONFIDENCE_RULES["exact_phrase"],
            "reason": "greeting detected"
        }

    # Employee lookup via ALL-CAPS
    caps_skip = {"HIGH", "RISK", "LOW", "MEDIUM", "STATUS", "STATUT", "CHECK", "HELLO", "HI", "BONJOUR", "SALUT"}

    has_employee_keyword = any(w in q for w in [
        "statut", "status", "employé", "employe", "employee",
        "cherche", "recherche", "trouve", "matricule", "nom"
    ])

    if caps_words := re.findall(r'\b[A-Z]{2,}(?:\s+[A-Z]{2,})*\b', question):
        filtered = [w for w in caps_words if w not in caps_skip]
        if filtered:
            return {
                "intent": "employee_lookup",
                "employee_name": " ".join(filtered).lower().strip(),
                "confidence": CONFIDENCE_RULES["caps_detection"],
                "reason": f"all-caps name detected: {filtered}"
            }

    if has_employee_keyword:
        caps = re.findall(r'\b[A-Z]{2,}\b', question)
        filtered = [w for w in caps if w not in caps_skip]
        if filtered:
            return {
                "intent": "employee_lookup",
                "employee_name": " ".join(filtered).lower().strip(),
                "confidence": CONFIDENCE_RULES["partial_context"],
                "reason": f"employee keyword + caps: {filtered}"
            }

    return {
        "intent": "general",
        "confidence": CONFIDENCE_RULES["general_fallback"],
        "reason": "no specific intent matched"
    }

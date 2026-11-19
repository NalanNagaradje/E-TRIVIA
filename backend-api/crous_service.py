
"""Client de l'API publique CROUStillant.

API : https://api.croustillant.menu

- Récupération de la liste des restaurants.
- Recherche des restaurants par nom ou ville.
- Récupération des menus par restaurant et par date.
- Mise en cache des réponses pour limiter les requêtes.
"""

import json
import os
import time
from datetime import datetime
from zoneinfo import ZoneInfo

import httpx


CROUS_API_URL = os.getenv(
    "CROUS_API_URL",
    "https://api.croustillant.menu/v1"
).rstrip("/")

CROUS_USER_AGENT = os.getenv(
    "CROUS_USER_AGENT",
    "E-Trivia/1.0 (projet etudiant Sorbonne Paris Nord)"
)

DEFAULT_RESTAURANT = os.getenv(
    "CROUS_RESTAURANT_CODE",
    ""
).strip()

CACHE_TTL = 600

_cache = {}


class CrousError(Exception):

    def __init__(self, message, status_code=502):
        super().__init__(message)
        self.message = message
        self.status_code = status_code


# ========== REQUETES API ==========

def _get(path):

    maintenant = time.time()

    en_cache = _cache.get(path)

    if en_cache and maintenant - en_cache[0] < CACHE_TTL:
        return en_cache[1]

    try:
        reponse = httpx.get(
            CROUS_API_URL + path,
            headers={
                "User-Agent": CROUS_USER_AGENT,
                "Accept": "application/json"
            },
            timeout=10.0
        )

    except httpx.HTTPError:
        raise CrousError(
            "L'API CROUStillant est injoignable pour le moment",
            502
        )

    if reponse.status_code == 400:
        raise CrousError(
            "Requête invalide envoyée à CROUStillant. "
            "Vérifiez le code du restaurant et la date.",
            400
        )

    if reponse.status_code == 404:
        raise CrousError(
            "Restaurant ou menu introuvable pour cette date",
            404
        )

    if reponse.status_code == 429:
        raise CrousError(
            "Trop de requêtes vers CROUStillant. "
            "Réessayez dans une minute.",
            429
        )

    if reponse.status_code >= 400:
        raise CrousError(
            "Erreur de l'API CROUStillant (" +
            str(reponse.status_code) + ")",
            502
        )

    try:
        donnees = reponse.json()
    except ValueError:
        raise CrousError(
            "La réponse de CROUStillant n'est pas un JSON valide",
            502
        )

    if len(_cache) > 500:
        _cache.clear()

    _cache[path] = (maintenant, donnees)

    return donnees


# ========== FORMAT DES REPONSES ==========

def _unwrap(donnees):

    if isinstance(donnees, dict) and "data" in donnees:
        return donnees["data"]

    return donnees


def _first(source, cles, defaut=None):

    if not isinstance(source, dict):
        return defaut

    for cle in cles:

        if source.get(cle) not in (None, ""):
            return source[cle]

    return defaut


# ========== RESTAURANTS ==========

def list_restaurants(search=None, limit=30):

    donnees = _unwrap(_get("/restaurants"))

    restaurants = donnees if isinstance(donnees, list) else []

    recherche = (search or "").strip().lower()

    resultats = []

    for resto in restaurants:

        if recherche and recherche not in json.dumps(
            resto,
            ensure_ascii=False
        ).lower():
            continue

        resultats.append({
            "code": _first(resto, ["code", "id"]),
            "name": _first(
                resto,
                ["nom", "name", "libelle"],
                "Restaurant"
            ),
            "address": _first(
                resto,
                ["adresse", "address"],
                ""
            )
        })

        if len(resultats) >= limit:
            break

    return resultats


# ========== NORMALISATION DES MENUS ==========

def _normalize_menu(donnees):

    contenu = _unwrap(donnees)

    jour = (
        contenu[0]
        if isinstance(contenu, list) and contenu
        else contenu
    )

    repas_liste = []

    for repas in _first(
        jour,
        ["repas", "meals", "services"],
        []
    ) or []:

        categories = []

        for categorie in _first(
            repas,
            ["categories", "plats_categories"],
            []
        ) or []:

            plats = []

            for plat in _first(
                categorie,
                ["plats", "dishes", "items"],
                []
            ) or []:

                nom = (
                    plat
                    if isinstance(plat, str)
                    else _first(
                        plat,
                        ["libelle", "nom", "name"]
                    )
                )

                if nom:
                    plats.append(str(nom))

            categories.append({
                "name": _first(
                    categorie,
                    ["libelle", "nom", "name"],
                    "Plats"
                ),
                "dishes": plats
            })

        repas_liste.append({
            "name": _first(
                repas,
                ["type", "nom", "libelle", "name"],
                "Repas"
            ),
            "categories": categories
        })

    return repas_liste


# ========== RECUPERATION DU MENU ==========

def get_menu(code, date=None, debug=False):

    # Date du jour par défaut : fuseau horaire français.
    if date is None:

        date = datetime.now(
            ZoneInfo("Europe/Paris")
        ).strftime("%Y-%m-%d")

    # Vérification du code restaurant.
    if code is None or not str(code).strip():

        raise CrousError(
            "Veuillez sélectionner un restaurant.",
            400
        )

    # Vérification de la date ISO reçue depuis E-Trivia.
    try:

        date_obj = datetime.strptime(
            date,
            "%Y-%m-%d"
        )

    except (TypeError, ValueError):

        raise CrousError(
            "Date invalide. Format attendu : AAAA-MM-JJ",
            400
        )

    # CORRECTION :
    # E-Trivia utilise AAAA-MM-JJ.
    # CROUStillant attend JJ-MM-AAAA.
    date_crous = date_obj.strftime("%d-%m-%Y")

    # Construction de l'URL CROUStillant.
    chemin = (
        "/restaurants/"
        + str(code).strip()
        + "/menu/"
        + date_crous
    )

    brut = _get(chemin)

    # Normalisation de la réponse.
    repas = _normalize_menu(brut)

    resultat = {
        "restaurant": code,
        "date": date,
        "meals": repas
    }

    if not repas:

        resultat["warning"] = (
            "Aucun plat trouvé. "
            "Le menu n'est peut-être pas encore publié, "
            "le restaurant peut être fermé ou le format "
            "de réponse peut être différent."
        )

    # Mode debug : réponse brute de l'API.
    if debug:

        resultat["raw"] = brut

    return resultat

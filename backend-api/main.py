from fastapi import FastAPI, Depends, HTTPException, Query, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBasic, HTTPBasicCredentials
from sqlalchemy.orm import Session
from typing import List, Optional
import hmac
import os
import models
import crous_service
from database import engine, get_db
from repository import (
    get_all_clubs, get_club_by_id, create_club, update_club, delete_club, change_password,
    authenticate_club,
    get_all_categories, create_category,
    get_all_events, get_event_by_id, create_event, delete_event,
    add_club_to_event, remove_club_from_event
)
from schemas import (
    ClubCreate, ClubUpdate, ClubResponse, PasswordChange, AuthInfo,
    CategoryCreate, CategoryResponse,
    EventCreate, EventResponse
)

# Créer les tables au démarrage
models.Base.metadata.create_all(bind=engine)

# Créer l'application FastAPI
app = FastAPI(
    title="E-Trivia API",
    description="""
    🍽️ **E-Trivia**

    API du site des étudiants de Sorbonne Paris Nord :
    le menu du restaurant universitaire et les clubs / associations.

    ## Fonctionnalités :
    - Menu du jour du Resto U (via l'API CROUStillant)
    - Gestion des clubs et associations (CRUD complet)
    - Gestion des événements
    - Co-organisation des événements par plusieurs clubs (Many-to-Many)
    - Gestion des catégories (One-to-Many)

    ## Relations :
    - **One-to-One** : Club → Contact
    - **One-to-Many** : Catégorie → Clubs
    - **Many-to-Many** : Clubs ↔ Événements

    ## Authentification :
    Les routes de modification demandent un identifiant et un mot de passe (HTTP Basic) :
    celui du club concerné, ou celui de l'administrateur.
    """,
    version="1.0.0",
)

# Ajouter CORS (le front PHP appelle l'API côté serveur : utile seulement pour d'autres clients web)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=False,
    allow_methods=["*"],
    allow_headers=["*"],
)

# ========== AUTHENTIFICATION ==========
ADMIN_USERNAME = os.getenv("ADMIN_USERNAME", "admin")
ADMIN_PASSWORD = os.getenv("ADMIN_PASSWORD", "admin1234")
basic = HTTPBasic(auto_error=False)

def get_current_user(credentials: Optional[HTTPBasicCredentials] = Depends(basic), db: Session = Depends(get_db)):
    """Renvoie {"role": "admin"} ou {"role": "club", "club": <le club connecté>}."""
    if credentials is None:
        raise HTTPException(status_code=401, detail="Identifiants requis", headers={"WWW-Authenticate": "Basic"})
    admin_ok = (hmac.compare_digest(credentials.username.encode(), ADMIN_USERNAME.encode())
                and hmac.compare_digest(credentials.password.encode(), ADMIN_PASSWORD.encode()))
    if admin_ok:
        return {"role": "admin", "club": None}
    club = authenticate_club(db, credentials.username, credentials.password)
    if club:
        return {"role": "club", "club": club}
    raise HTTPException(status_code=401, detail="Identifiants invalides", headers={"WWW-Authenticate": "Basic"})

def require_admin(user: dict = Depends(get_current_user)):
    if user["role"] != "admin":
        raise HTTPException(status_code=403, detail="Réservé à l'administrateur")
    return user

def check_club_access(user: dict, club_id: int):
    """Autorise l'administrateur ou le club propriétaire."""
    if user["role"] == "admin":
        return
    if user["club"].id != club_id:
        raise HTTPException(status_code=403, detail="Vous ne pouvez modifier que votre propre club")

# ========== ROUTES RACINE ==========
@app.get("/", tags=["Home"])
def home():
    """
    Point d'entrée de l'API E-Trivia
    """
    return {
        "message": "🍽️ E-Trivia API",
        "version": "1.0.0",
        "documentation": "/docs",
        "endpoints": {
            "menu": "/menu",
            "clubs": "/clubs",
            "events": "/events",
            "categories": "/categories"
        }
    }

@app.get("/auth/me", response_model=AuthInfo, tags=["Auth"])
def who_am_i(user: dict = Depends(get_current_user)):
    """
    Vérifie les identifiants (HTTP Basic) et renvoie le rôle : admin ou club.
    """
    club = user["club"]
    return AuthInfo(role=user["role"], club_id=club.id if club else None, club_name=club.name if club else None)

# ========== MENU DU RESTO U ==========
@app.get("/menu/restaurants", tags=["Menu"])
def search_restaurants(search: Optional[str] = Query(None, max_length=60)):
    """
    Recherche un restaurant CROUS pour obtenir son code.

    - **search** : mot-clé (ex: Villetaneuse)
    """
    try:
        return crous_service.list_restaurants(search)
    except crous_service.CrousError as exc:
        raise HTTPException(status_code=exc.status_code, detail=exc.message)

@app.get("/menu", tags=["Menu"])
def get_menu(
    restaurant: Optional[str] = Query(None, max_length=50, pattern=r"^[A-Za-z0-9_-]+$"),
    date: Optional[str] = Query(None, pattern=r"^\d{4}-\d{2}-\d{2}$"),
    debug: bool = False
):
    """
    Récupère le menu d'un restaurant universitaire.

    - **restaurant** : code du restaurant (sinon celui de la configuration)
    - **date** : AAAA-MM-JJ (par défaut : aujourd'hui)
    - **debug** : renvoie aussi la réponse brute de CROUStillant
    """
    code = restaurant or crous_service.DEFAULT_RESTAURANT
    if not code:
        raise HTTPException(status_code=400, detail="Aucun restaurant sélectionné : choisissez-en un dans la page Menu")
    try:
        return crous_service.get_menu(code, date, debug)
    except crous_service.CrousError as exc:
        raise HTTPException(status_code=exc.status_code, detail=exc.message)

# ========== CLUBS ENDPOINTS ==========
@app.get("/clubs", response_model=List[ClubResponse], tags=["Clubs"])
def get_clubs(category_id: Optional[int] = None, db: Session = Depends(get_db)):
    """
    Récupère la liste de tous les clubs et associations.

    - **category_id** : filtre par catégorie (optionnel)
    """
    return get_all_clubs(db, category_id)

@app.get("/clubs/{club_id}", response_model=ClubResponse, tags=["Clubs"])
def get_club(club_id: int, db: Session = Depends(get_db)):
    """
    Récupère la présentation complète d'un club.

    - **club_id** : L'identifiant unique du club
    - **Retourne** : description, bannière, contact, catégorie et événements
    """
    club = get_club_by_id(db, club_id)
    if not club:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=f"Club {club_id} introuvable"
        )
    return club

@app.post("/clubs", response_model=ClubResponse, status_code=status.HTTP_201_CREATED, tags=["Clubs"])
def add_club(club: ClubCreate, db: Session = Depends(get_db), user: dict = Depends(require_admin)):
    """
    Crée un club et ses identifiants de connexion (administrateur uniquement).

    - **name** : Nom du club (obligatoire)
    - **username** : Identifiant de connexion du club
    - **password** : Mot de passe initial (8 caractères minimum)
    - **category_id** : Catégorie
    - **contact** : email, instagram, site web (optionnel)

    Exemple de requête :
    ```json
    {
        "name": "Club Échecs",
        "username": "club-echecs",
        "password": "motdepasse123",
        "description": "Tournois et initiation aux échecs",
        "category_id": 4,
        "contact": {"email": "echecs@example.org"}
    }
    ```
    """
    try:
        return create_club(db, club)
    except ValueError as exc:
        raise HTTPException(status_code=409, detail=str(exc))

@app.put("/clubs/{club_id}", response_model=ClubResponse, tags=["Clubs"])
def update_club_route(club_id: int, club_update: ClubUpdate, db: Session = Depends(get_db), user: dict = Depends(get_current_user)):
    """
    Modifie la présentation d'un club (le club concerné ou l'administrateur).

    - **club_id** : Identifiant du club
    - **name**, **description**, **banner_url**, **category_id**, **contact** : tous optionnels
    """
    check_club_access(user, club_id)
    try:
        club = update_club(db, club_id, club_update)
    except ValueError as exc:
        raise HTTPException(status_code=409, detail=str(exc))
    if not club:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=f"Club {club_id} introuvable"
        )
    return club

@app.put("/clubs/{club_id}/password", tags=["Clubs"])
def change_password_route(club_id: int, body: PasswordChange, db: Session = Depends(get_db), user: dict = Depends(get_current_user)):
    """
    Change le mot de passe d'un club (le club concerné ou l'administrateur).
    """
    check_club_access(user, club_id)
    if not change_password(db, club_id, body.new_password):
        raise HTTPException(status_code=404, detail=f"Club {club_id} introuvable")
    return {"message": "Mot de passe modifié"}

@app.delete("/clubs/{club_id}", tags=["Clubs"])
def delete_club_route(club_id: int, db: Session = Depends(get_db), user: dict = Depends(require_admin)):
    """
    Supprime un club (administrateur uniquement).

    - **club_id** : Identifiant du club à supprimer
    """
    result = delete_club(db, club_id)
    if not result:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=f"Club {club_id} introuvable"
        )
    return {"message": f"Club {club_id} supprimé"}

# ========== EVENTS ENDPOINTS ==========
@app.get("/events", response_model=List[EventResponse], tags=["Events"])
def get_events(include_past: bool = False, db: Session = Depends(get_db)):
    """
    Récupère les événements à venir de tous les clubs.

    - **include_past** : true pour afficher aussi les événements passés
    """
    return get_all_events(db, include_past)

@app.get("/events/{event_id}", response_model=EventResponse, tags=["Events"])
def get_event(event_id: int, db: Session = Depends(get_db)):
    """
    Récupère le détail d'un événement.

    - **event_id** : Identifiant de l'événement
    """
    event = get_event_by_id(db, event_id)
    if not event:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail=f"Événement {event_id} introuvable"
        )
    return event

@app.post("/events", response_model=EventResponse, status_code=status.HTTP_201_CREATED, tags=["Events"])
def add_event(event: EventCreate, db: Session = Depends(get_db), user: dict = Depends(get_current_user)):
    """
    Crée un événement pour le club connecté (compte club obligatoire).

    - **title** : Titre (obligatoire)
    - **date** : Date et heure (ex: 2026-12-05T18:00)
    - **location** : Lieu
    - **description** : Description
    """
    if user["role"] != "club":
        raise HTTPException(status_code=403, detail="Connectez-vous avec le compte d'un club pour créer un événement")
    return create_event(db, user["club"].id, event)

@app.post("/events/{event_id}/clubs/{club_id}", tags=["Events"])
def assign_club_to_event(event_id: int, club_id: int, db: Session = Depends(get_db), user: dict = Depends(get_current_user)):
    """
    Ajoute un club comme co-organisateur d'un événement (Many-to-Many).

    - **event_id** : Identifiant de l'événement
    - **club_id** : Identifiant du club (le vôtre, sauf administrateur)
    """
    check_club_access(user, club_id)
    result = add_club_to_event(db, club_id, event_id)
    if not result:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Club ou événement introuvable"
        )
    return {"message": f"Club {club_id} co-organisateur de l'événement {event_id}"}

@app.delete("/events/{event_id}/clubs/{club_id}", tags=["Events"])
def remove_club_from_event_route(event_id: int, club_id: int, db: Session = Depends(get_db), user: dict = Depends(get_current_user)):
    """
    Retire un club d'un événement. Sans organisateur, l'événement est supprimé.
    """
    check_club_access(user, club_id)
    result = remove_club_from_event(db, club_id, event_id)
    if not result:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Club ou événement introuvable, ou club non organisateur"
        )
    return {"message": f"Club {club_id} retiré de l'événement {event_id}"}

@app.delete("/events/{event_id}", tags=["Events"])
def delete_event_route(event_id: int, db: Session = Depends(get_db), user: dict = Depends(get_current_user)):
    """
    Supprime un événement (un des clubs organisateurs ou l'administrateur).

    - **event_id** : Identifiant de l'événement
    """
    event = get_event_by_id(db, event_id)
    if not event:
        raise HTTPException(status_code=404, detail="Événement introuvable")
    if user["role"] != "admin" and user["club"].id not in [c.id for c in event.clubs]:
        raise HTTPException(status_code=403, detail="Seul un club organisateur peut supprimer cet événement")
    delete_event(db, event_id)
    return {"message": f"Événement {event_id} supprimé"}

# ========== CATEGORIES ENDPOINTS ==========
@app.get("/categories", response_model=List[CategoryResponse], tags=["Categories"])
def get_categories(db: Session = Depends(get_db)):
    """
    Récupère la liste de toutes les catégories de clubs.
    """
    return get_all_categories(db)

@app.post("/categories", response_model=CategoryResponse, status_code=status.HTTP_201_CREATED, tags=["Categories"])
def add_category(category: CategoryCreate, db: Session = Depends(get_db), user: dict = Depends(require_admin)):
    """
    Crée une nouvelle catégorie (administrateur uniquement).

    - **name** : Nom de la catégorie (ex: Sport)
    """
    try:
        return create_category(db, category.name)
    except ValueError as exc:
        raise HTTPException(status_code=409, detail=str(exc))

# Point d'entrée pour uvicorn
if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=5000)

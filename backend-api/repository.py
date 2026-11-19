from sqlalchemy.orm import Session, selectinload
from sqlalchemy.exc import IntegrityError
from models import Category, Club, ClubContact, Event
from schemas import ClubCreate, ClubUpdate, EventCreate
from datetime import datetime
from zoneinfo import ZoneInfo
import hashlib
import hmac
import secrets

ITERATIONS = 200000

# ========== MOTS DE PASSE (hashés, jamais stockés en clair) ==========
def hash_password(password: str):
    salt = secrets.token_bytes(16)
    digest = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, ITERATIONS)
    return salt.hex() + "$" + digest.hex()

def verify_password(password: str, stored: str):
    try:
        salt_hex, digest_hex = stored.split("$")
        salt = bytes.fromhex(salt_hex)
    except ValueError:
        return False
    digest = hashlib.pbkdf2_hmac("sha256", password.encode("utf-8"), salt, ITERATIONS)
    return hmac.compare_digest(digest.hex(), digest_hex)

# Faux hash : évite de révéler par le temps de réponse qu'un identifiant n'existe pas
_FAUX_HASH = hash_password(secrets.token_hex(8))

def authenticate_club(db: Session, username: str, password: str):
    club = db.query(Club).filter(Club.username == username).first()
    valide = verify_password(password, club.password_hash if club else _FAUX_HASH)
    return club if (club and valide) else None

def maintenant():
    """Heure de Paris sans fuseau, comme stockée en base."""
    return datetime.now(ZoneInfo("Europe/Paris")).replace(tzinfo=None)

# ========== CLUBS ==========
def get_all_clubs(db: Session, category_id: int = None):
    query = db.query(Club).options(
        selectinload(Club.contact), selectinload(Club.category), selectinload(Club.events)
    )
    if category_id:
        query = query.filter(Club.category_id == category_id)
    return query.order_by(Club.name).all()

def get_club_by_id(db: Session, club_id: int):
    return db.query(Club).filter(Club.id == club_id).first()

def create_club(db: Session, data: ClubCreate):
    if data.category_id and not db.query(Category).filter(Category.id == data.category_id).first():
        raise ValueError("Catégorie introuvable")
    club = Club(
        name=data.name,
        username=data.username,
        password_hash=hash_password(data.password),
        description=data.description,
        banner_url=data.banner_url,
        category_id=data.category_id
    )
    club.contact = ClubContact(**(data.contact.model_dump() if data.contact else {}))
    db.add(club)
    try:
        db.commit()
    except IntegrityError:
        db.rollback()
        raise ValueError("Ce nom de club ou cet identifiant de connexion est déjà utilisé")
    db.refresh(club)
    return club

def update_club(db: Session, club_id: int, data: ClubUpdate):
    club = get_club_by_id(db, club_id)
    if not club:
        return None
    valeurs = data.model_dump(exclude_unset=True)
    contact = valeurs.pop("contact", None)

    if valeurs.get("category_id") and not db.query(Category).filter(Category.id == valeurs["category_id"]).first():
        raise ValueError("Catégorie introuvable")
    for champ, valeur in valeurs.items():
        if champ == "name" and valeur is None:
            continue  # le nom est obligatoire
        setattr(club, champ, valeur)

    if contact is not None:
        if club.contact is None:
            club.contact = ClubContact()
        for champ, valeur in contact.items():
            setattr(club.contact, champ, valeur)
    try:
        db.commit()
    except IntegrityError:
        db.rollback()
        raise ValueError("Ce nom de club est déjà utilisé")
    db.refresh(club)
    return club

def change_password(db: Session, club_id: int, new_password: str):
    club = get_club_by_id(db, club_id)
    if not club:
        return False
    club.password_hash = hash_password(new_password)
    db.commit()
    return True

def delete_club(db: Session, club_id: int):
    club = get_club_by_id(db, club_id)
    if not club:
        return False
    # Les événements organisés uniquement par ce club disparaissent avec lui
    for event in list(club.events):
        if len(event.clubs) == 1:
            db.delete(event)
    db.delete(club)
    db.commit()
    return True

# ========== CATEGORIES ==========
def get_all_categories(db: Session):
    return db.query(Category).order_by(Category.name).all()

def create_category(db: Session, name: str):
    category = Category(name=name)
    db.add(category)
    try:
        db.commit()
    except IntegrityError:
        db.rollback()
        raise ValueError("Cette catégorie existe déjà")
    db.refresh(category)
    return category

# ========== EVENEMENTS ==========
def get_all_events(db: Session, include_past: bool = False):
    query = db.query(Event).options(selectinload(Event.clubs))
    if not include_past:
        query = query.filter(Event.date >= maintenant())
    return query.order_by(Event.date).all()

def get_event_by_id(db: Session, event_id: int):
    return db.query(Event).options(selectinload(Event.clubs)).filter(Event.id == event_id).first()

def create_event(db: Session, club_id: int, data: EventCreate):
    club = get_club_by_id(db, club_id)
    if not club:
        return None
    event = Event(**data.model_dump())
    event.clubs.append(club)
    db.add(event)
    db.commit()
    db.refresh(event)
    return event

def delete_event(db: Session, event_id: int):
    event = get_event_by_id(db, event_id)
    if event:
        db.delete(event)
        db.commit()
        return True
    return False

def add_club_to_event(db: Session, club_id: int, event_id: int):
    club = get_club_by_id(db, club_id)
    event = get_event_by_id(db, event_id)
    if club and event:
        if club not in event.clubs:
            event.clubs.append(club)
            db.commit()
        return True
    return False

def remove_club_from_event(db: Session, club_id: int, event_id: int):
    club = get_club_by_id(db, club_id)
    event = get_event_by_id(db, event_id)
    if club and event and club in event.clubs:
        event.clubs.remove(club)
        if not event.clubs:
            db.delete(event)  # plus aucun organisateur : l'événement disparaît
        db.commit()
        return True
    return False

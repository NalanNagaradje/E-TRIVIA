from pydantic import BaseModel, Field, field_validator
from typing import Optional, List
from datetime import datetime


def verifier_url(valeur):
    """Une URL doit commencer par http:// ou https:// (évite les liens dangereux)."""
    if valeur is None or valeur == "":
        return None
    if not valeur.lower().startswith(("http://", "https://")):
        raise ValueError("L'URL doit commencer par http:// ou https://")
    return valeur

# ========== CATEGORY SCHEMAS ==========
class CategoryBase(BaseModel):
    name: str = Field(min_length=2, max_length=50)

class CategoryCreate(CategoryBase):
    pass

class CategoryResponse(CategoryBase):
    id: int

    class Config:
        from_attributes = True

# ========== CONTACT SCHEMAS ==========
class ContactBase(BaseModel):
    email: Optional[str] = Field(default=None, max_length=150)
    instagram: Optional[str] = Field(default=None, max_length=150)
    website: Optional[str] = Field(default=None, max_length=255)

    @field_validator("website")
    @classmethod
    def verifier_site(cls, valeur):
        return verifier_url(valeur)

class ContactCreate(ContactBase):
    pass

class ContactResponse(ContactBase):
    class Config:
        from_attributes = True

# ========== EVENT SCHEMAS ==========
class EventBase(BaseModel):
    title: str = Field(min_length=2, max_length=200)
    description: Optional[str] = Field(default=None, max_length=5000)
    date: datetime
    location: Optional[str] = Field(default=None, max_length=200)

class EventCreate(EventBase):
    pass

class ClubSummary(BaseModel):
    id: int
    name: str

    class Config:
        from_attributes = True

class EventResponse(EventBase):
    id: int
    clubs: List[ClubSummary] = []

    class Config:
        from_attributes = True

class EventSummary(EventBase):
    id: int

    class Config:
        from_attributes = True

# ========== CLUB SCHEMAS ==========
class ClubBase(BaseModel):
    name: str = Field(min_length=2, max_length=100)
    description: Optional[str] = Field(default=None, max_length=5000)
    banner_url: Optional[str] = Field(default=None, max_length=500)
    category_id: Optional[int] = None

    @field_validator("banner_url")
    @classmethod
    def verifier_banniere(cls, valeur):
        return verifier_url(valeur)

class ClubCreate(ClubBase):
    username: str = Field(min_length=3, max_length=50, pattern=r"^[A-Za-z0-9._-]+$")
    password: str = Field(min_length=8, max_length=128)
    contact: Optional[ContactCreate] = None

class ClubUpdate(BaseModel):
    name: Optional[str] = Field(default=None, min_length=2, max_length=100)
    description: Optional[str] = Field(default=None, max_length=5000)
    banner_url: Optional[str] = Field(default=None, max_length=500)
    category_id: Optional[int] = None
    contact: Optional[ContactBase] = None

    @field_validator("banner_url")
    @classmethod
    def verifier_banniere(cls, valeur):
        return verifier_url(valeur)

class ClubResponse(ClubBase):
    """Réponse publique : n'expose jamais l'identifiant de connexion ni le mot de passe."""
    id: int
    category: Optional[CategoryResponse] = None
    contact: Optional[ContactResponse] = None
    events: List[EventSummary] = []

    class Config:
        from_attributes = True

class PasswordChange(BaseModel):
    new_password: str = Field(min_length=8, max_length=128)

class AuthInfo(BaseModel):
    role: str
    club_id: Optional[int] = None
    club_name: Optional[str] = None

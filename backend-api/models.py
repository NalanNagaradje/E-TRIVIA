from sqlalchemy import Column, Integer, String, ForeignKey, Table, DateTime, Text
from sqlalchemy.orm import relationship
from database import Base

# Table Many-to-Many : Club <-> Événement (un événement peut être co-organisé par plusieurs clubs)
club_event = Table(
    'club_event',
    Base.metadata,
    Column('club_id', Integer, ForeignKey('clubs.id', ondelete='CASCADE'), primary_key=True),
    Column('event_id', Integer, ForeignKey('events.id', ondelete='CASCADE'), primary_key=True)
)

class Category(Base):
    __tablename__ = 'categories'

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(50), nullable=False, unique=True)

    clubs = relationship("Club", back_populates="category")

class Club(Base):
    __tablename__ = 'clubs'

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(100), nullable=False, unique=True)
    username = Column(String(50), nullable=False, unique=True)
    password_hash = Column(String(255), nullable=False)
    description = Column(Text)
    banner_url = Column(String(500))
    category_id = Column(Integer, ForeignKey('categories.id'))

    category = relationship("Category", back_populates="clubs")
    contact = relationship("ClubContact", back_populates="club", uselist=False, cascade="all, delete-orphan")
    events = relationship("Event", secondary=club_event, back_populates="clubs")

class ClubContact(Base):
    __tablename__ = 'club_contacts'

    id = Column(Integer, primary_key=True, index=True)
    club_id = Column(Integer, ForeignKey('clubs.id', ondelete='CASCADE'), unique=True)
    email = Column(String(150))
    instagram = Column(String(150))
    website = Column(String(255))

    club = relationship("Club", back_populates="contact")

class Event(Base):
    __tablename__ = 'events'

    id = Column(Integer, primary_key=True, index=True)
    title = Column(String(200), nullable=False)
    description = Column(Text)
    date = Column(DateTime, nullable=False)
    location = Column(String(200))

    clubs = relationship("Club", secondary=club_event, back_populates="events")

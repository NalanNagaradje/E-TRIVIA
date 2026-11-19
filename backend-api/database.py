from sqlalchemy import create_engine
from sqlalchemy.engine import URL
from sqlalchemy.exc import OperationalError
from sqlalchemy.orm import declarative_base, sessionmaker
import os
import time

DB_HOST = os.getenv('DB_HOST', 'database')
DB_PORT = int(os.getenv('DB_PORT', '3306'))
DB_USER = os.getenv('DB_USER', 'root')
DB_PASSWORD = os.getenv('DB_PASSWORD', 'root')
DB_NAME = os.getenv('DB_NAME', 'etrivia_db')

DATABASE_URL = URL.create(
    "mysql+pymysql",
    username=DB_USER,
    password=DB_PASSWORD,
    host=DB_HOST,
    port=DB_PORT,
    database=DB_NAME,
    query={"charset": "utf8mb4"},
)


def create_engine_with_retry(url, retries=15, delay=3):
    """Attend que MySQL accepte vraiment des connexions avant de continuer."""
    for attempt in range(1, retries + 1):
        try:
            engine = create_engine(url, pool_pre_ping=True)
            with engine.connect():
                pass
            print(f"[DB] Connexion réussie (tentative {attempt})")
            return engine
        except OperationalError:
            print(f"[DB] MySQL pas encore prêt ({attempt}/{retries}), nouvel essai dans {delay}s...")
            time.sleep(delay)
    raise RuntimeError("Impossible de se connecter à MySQL après plusieurs tentatives")


engine = create_engine_with_retry(DATABASE_URL)
SessionLocal = sessionmaker(autocommit=False, autoflush=False, bind=engine)
Base = declarative_base()

def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()

# E-Trivia
 
> Plateforme web pour les étudiants de **Sorbonne Paris Nord** : consulter le **menu du Crous** avant de faire la queue, et découvrir les **clubs et associations** de l'université.
 
---
 
## 📖 Présentation du projet
 
**E-Trivia** est un site web proposant deux services distincts :
 
1. 🍽️ **Le menu du Crous** : une page affichant le menu du restaurant universitaire grâce à l'API publique [CROUStillant](https://croustillant.menu).
2. 🎓 **Les clubs et associations** : une page de présentations des différents clubs, associations et événements organisés de l'université, remplies par les intéressés eux-mêmes. Chaque présentation contient une **bannière**, une **description**, une **liste d'événements** et les **moyens de contacter** l'association.

Dans un premier temps, le site s'adresse uniquement aux étudiants de **Sorbonne Paris Nord**.

---
 
## ✨ Fonctionnalités
 
- 🍽️ Menu du restaurant universitaire (API [CROUStillant](https://croustillant.menu)), avec recherche du restaurant et choix de la date
- 🎓 Présentation des clubs : bannière, description, contacts, catégorie
- 📅 Événements des clubs
- 🤝 Co-organisation d'un événement par plusieurs clubs
- 🏷️ Catégories de clubs (Sport, Culture, Musique, Informatique…)
- 🔑 Espace club protégé par identifiant et mot de passe
- 🖥️ Interface web en PHP
- 📖 Documentation Swagger automatique
---
 
## 📦 Installation et lancement
 
### 🔧 Prérequis
 
- [Docker Desktop](https://www.docker.com/products/docker-desktop/)
- [Git](https://git-scm.com/)
### 🚀 Lancer le projet
 
```bash
git clone https://github.com/NalanNagaradje/E-TRIVIA.git
cd E-TRIVIA
docker compose up -d
```
Cliquez sur ce lien pour tester le site web : [http://localhost:8080](http://localhost:8080)

### ⛔ Arrêter le projet
 
```bash
docker compose down
```
 
---
 
## 🌐 Accès aux services
 
| Service | URL | Description |
|---------|-----|-------------|
| 🖥️ Interface PHP | [http://localhost:8080](http://localhost:8080) | Site E-Trivia |
| 🔌 API REST | [http://localhost:5050](http://localhost:5050) | Endpoints JSON |
| 📖 Swagger UI | [http://localhost:5050/docs](http://localhost:5050/docs) | Documentation interactive |
 
### 🔑 Comptes de démonstration
 
| Rôle | Identifiant | Mot de passe |
|------|-------------|--------------|
| Administrateur | `admin` | `admin1234` (défini dans `docker-compose.yml`, **à changer**) |
| Club | `club-info`, `asso-sport`, `atelier-musique` | `club1234` |
 
L'administrateur crée les clubs et leurs identifiants. Chaque club modifie ensuite sa propre présentation et gère ses événements.

---
 
## 🏗️ Structure du projet
 
```
Projet_E_Trivia/
│
├── README.md                          # Documentation du projet
├── .gitignore                         # Fichiers à ignorer
├── docker-compose.yml                 # Orchestration Docker
│
├── backend-api/                       # API FastAPI
│   ├── Dockerfile                     # Construction image Docker
│   ├── requirements.txt               # Dépendances Python
│   ├── main.py                        # Routes + authentification
│   ├── models.py                      # Modèles SQLAlchemy
│   ├── schemas.py                     # Schémas Pydantic
│   ├── repository.py                  # Couche DAO
│   ├── crous_service.py               # Client de l'API CROUStillant
│   └── database.py                    # Connexion MySQL
│
├── frontend-php/                      # Interface PHP
│   ├── Dockerfile                     # Construction image Docker
│   ├── index.php                      # Contrôleur principal
│   ├── config/
│   │   └── config.php                 # Configuration API
│   ├── services/
│   │   └── TriviaService.php          # Appels API
│   ├── views/                         # Les différentes vues
│   │   ├── accueil.php
│   │   ├── menu.php
│   │   ├── clubs.php
│   │   ├── evenements.php
│   │   └── categories.php
│   └── assets/
│       └── style.css                  # Feuille de style
│
└── database/                          # Serveur de base de données
    └── 01-init.sql                    # Création des tables + données de test
```
 
> **💾 Persistance des données** : le volume Docker (`db_data`) conserve les données MySQL entre deux redémarrages.
 
> **🌐 Réseau Docker** : le réseau **bridge personnalisé** (`etrivia-network`) relie les trois services et permet de les joindre par leur nom (`database`, `api`, `web`).

---

## 🛠️ Technologies
 
| Technologie | Usage |
|------------|-------|
| FastAPI | Framework API REST (Python) |
| SQLAlchemy | ORM |
| MySQL | Base de données |
| httpx | Appels à l'API CROUStillant |
| PHP | Interface frontend |
| Docker | Conteneurisation |
| Docker Compose | Orchestration |

---

## 🔗 Routes API
 
### 🍽️ Menu
 
| Méthode | Endpoint | Accès | Description |
|---------|----------|-------|-------------|
| `GET` | `/menu/restaurants?search=` | public | Trouver le code d'un restaurant |
| `GET` | `/menu?restaurant=&date=` | public | Menu d'un restaurant (aujourd'hui par défaut) |
 
### 🎓 Clubs
 
| Méthode | Endpoint | Accès | Description |
|---------|----------|-------|-------------|
| `GET` | `/clubs` | public | Liste des clubs (`?category_id=`) |
| `GET` | `/clubs/{id}` | public | Présentation d'un club |
| `POST` | `/clubs` | admin | Créer un club |
| `PUT` | `/clubs/{id}` | club concerné | Modifier la présentation |
| `PUT` | `/clubs/{id}/password` | club concerné | Changer le mot de passe |
| `DELETE` | `/clubs/{id}` | admin | Supprimer un club |
 
### 📅 Événements
 
| Méthode | Endpoint | Accès | Description |
|---------|----------|-------|-------------|
| `GET` | `/events` | public | Événements à venir (`?include_past=true`) |
| `GET` | `/events/{id}` | public | Détail d'un événement |
| `POST` | `/events` | club | Créer un événement pour son club |
| `DELETE` | `/events/{id}` | club organisateur | Supprimer un événement |
| `POST` | `/events/{id}/clubs/{club_id}` | club concerné | Co-organiser un événement |
| `DELETE` | `/events/{id}/clubs/{club_id}` | club concerné | Retirer son club d'un événement |
 
### 🏷️ Catégories
 
| Méthode | Endpoint | Accès | Description |
|---------|----------|-------|-------------|
| `GET` | `/categories` | public | Liste des catégories |
| `POST` | `/categories` | admin | Ajouter une catégorie |
 
### 🔐 Authentification
 
| Méthode | Endpoint | Accès | Description |
|---------|----------|-------|-------------|
| `GET` | `/auth/me` | admin ou club concerne | Vérifier ses identifiants et son rôle |
 
---
  
## 📊 Modélisation des relations ORM
 
| Relation | Type | Tables |
|----------|------|--------|
| Club ↔ Contact | One-to-One | `clubs` ↔ `club_contacts` |
| Catégorie ↔ Clubs | One-to-Many | `categories` ↔ `clubs` |
| Club ↔ Événement | Many-to-Many | `clubs` ↔ `events` (via `club_event`) |
 
---
 
## 🔒 Sécurité
 
- Les mots de passe des clubs sont **hachés** (PBKDF2-SHA256 avec sel) et jamais stockés en clair.
- Les routes de modification sont protégées par **HTTP Basic** : seul le club concerné (ou l'administrateur) peut modifier sa présentation.

---
 
## 👨‍💻 Équipe
 
Ce projet a été réalisé par les 3 étudiants suivants (de Sorbonne Paris Nord) : 
 
| Nom | Prénom | Numéro étudiant |
|-----|--------|-----------------|
| NAGARADJE | NALAN | 12103285 |
| MARTEL | MATHURIN | 12101145 |
| NAGARADJE | NANDAN | 12101912 |
 
---
 

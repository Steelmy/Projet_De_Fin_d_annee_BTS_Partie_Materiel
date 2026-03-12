# Gestion de Matériel

Application web de gestion d'inventaire matériel. Permet la gestion complète d'objets (ajout, modification, suppression, consultation) et de caisses (regroupement d'objets).

## Architecture

```mermaid
graph TB
    subgraph "Frontend"
        HTML["Gestion_materiel.html"]
        CSS["css/output.css (Tailwind)"]
        JS_CORE["javascript/"]
        JS_CORE --> SORT["sortUtils.js"]
        JS_CORE --> AC["universalAutocomplete.js"]
        JS_CORE --> ACB["universalAutocompleteBarcode.js"]
        JS_CORE --> INIT["initAutocompletes.js"]
        JS_CORE --> CRUD_JS["add/delete/updateItem.js"]
        JS_CORE --> CAISSE_JS["add/delete/updateBox.js"]
        JS_CORE --> FILTER["filterConsultation.js"]
    end

    subgraph "Backend PHP"
        BOOTSTRAP["core/bootstrap.php"]
        BOOTSTRAP --> ENV["core/EnvLoader.php"]
        BOOTSTRAP --> LOG["core/Logger.php"]
        BOOTSTRAP --> DB["core/Database.php"]
        BOOTSTRAP --> API["core/ApiResponse.php"]

        subgraph "Endpoints"
            MATERIEL_API["add/delete/update/get_materiel*.php"]
            CAISSE_API["add/delete/update/get_caisse*.php"]
            SEARCH_API["searchUniversal.php / searchBarcodes.php"]
            UTILS_API["checkBarcode.php / getIds.php / getUsers.php"]
            MONITOR["monitor.php"]
        end
    end

    subgraph "Données"
        ENV_FILE[".env"]
        LOGS["logs/app-YYYY-MM-DD.log"]
        BDD["MySQL/MariaDB"]
    end

    HTML --> JS_CORE
    HTML --> CSS
    JS_CORE -->|"fetch() API"| MATERIEL_API
    JS_CORE -->|"fetch() API"| CAISSE_API
    JS_CORE -->|"fetch() API"| SEARCH_API
    BOOTSTRAP --> ENV_FILE
    LOG --> LOGS
    DB --> BDD
```

## Structure des fichiers

```
├── .env                    # Variables d'environnement (secrets)
├── .env.example            # Template .env (commité)
├── .gitignore
├── README.md
├── Gestion_materiel.html   # Page principale (SPA)
├── BDD/                    # Script SQL de création de la BDD
├── css/
│   ├── input.css           # Source Tailwind
│   └── output.css          # CSS compilé
├── javascript/
│   ├── sortUtils.js            # Utilitaire de tri centralisé (DRY)
│   ├── universalAutocomplete.js # Autocomplétion texte
│   ├── universalAutocompleteBarcode.js # Autocomplétion code-barre
│   ├── initAutocompletes.js    # Initialisation des autocomplétions
│   ├── filterConsultation.js   # Filtres et tri de l'inventaire
│   ├── addItem.js          # Formulaire ajout matériel
│   ├── deleteItem.js       # Formulaire suppression matériel
│   ├── updateItem.js       # Formulaire modification matériel
│   ├── addBox.js            # Formulaire ajout caisse
│   ├── deleteBox.js         # Formulaire suppression caisse
│   ├── updateBox.js         # Formulaire modification caisse
│   ├── barcodeGenerator.js     # Génération de codes-barres
│   ├── downloadPdf.js          # Export PDF
│   ├── textFieldLoader.js       # Chargement dynamique des champs
│   ├── boxFormToggle.js    # Toggle formulaires caisse
│   └── formActions.js          # Actions formulaires
├── php/
│   ├── core/                    # Infrastructure commune
│   │   ├── bootstrap.php        # Point d'entrée unique
│   │   ├── EnvLoader.php        # Chargement .env
│   │   ├── Logger.php           # Système de logging
│   │   ├── Database.php         # Connexion PDO
│   │   └── ApiResponse.php      # Réponses JSON standardisées
│   ├── dbConnect.php           # Wrapper rétrocompatible
│   ├── monitor.php              # Health check & monitoring
│   ├── addItem.php         # POST — Ajouter matériel
│   ├── deleteItem.php      # POST — Supprimer matériel
│   ├── updateItem.php      # POST — Modifier matériel
│   ├── getAllItems.php     # GET — Liste tous les matériels
│   ├── getItemDetails.php # GET — Détails d'un matériel
│   ├── addBox.php           # POST — Ajouter caisse
│   ├── deleteBox.php        # POST — Supprimer caisse
│   ├── updateBox.php        # POST — Modifier caisse
│   ├── getAllBoxes.php      # GET — Liste toutes les caisses
│   ├── getBoxDetails.php   # GET — Détails d'une caisse
│   ├── getAvailableObjects.php # GET — Objets disponibles
│   ├── searchBarcodes.php  # GET — Recherche codes-barres
│   ├── searchUniversal.php     # GET — Recherche universelle
│   ├── checkBarcode.php        # GET — Vérifier unicité code-barre
│   ├── getIds.php              # GET — IDs par type/nom
│   ├── getUsers.php     # GET — Liste utilisateurs
│   └── generateInventoryPdf.php # GET — Génération PDF
└── logs/                        # Logs applicatifs (gitignored)
    └── app-YYYY-MM-DD.log
```

## Installation

### Prérequis

- XAMPP (Apache + MySQL/MariaDB + PHP 8.0+)
- Node.js (pour Tailwind CSS, optionnel)

### Configuration

1. **Cloner le projet** dans le dossier `htdocs` de XAMPP
2. **Configurer la base de données** :
   ```bash
   cp .env.example .env
   ```
   Éditer `.env` avec vos identifiants BDD
3. **Importer la base de données** :
   ```sql
   source BDD/BDD_test_gestion_materiel_mariadb.sql
   ```
4. **Démarrer XAMPP** (Apache + MySQL)
5. **Accéder à l'application** : `http://localhost/Projet de fin d'année BTS/Gestion_materiel.html`

## Principes appliqués

### SOLID

- **Single Responsibility** : Chaque classe PHP a une responsabilité unique
  - `EnvLoader` → chargement des variables d'environnement
  - `Logger` → logging applicatif
  - `Database` → connexion BDD
  - `ApiResponse` → formatage des réponses API

### DRY (Don't Repeat Yourself)

- `bootstrap.php` remplace le boilerplate dupliqué dans 16 endpoints
- `ApiResponse` centralise le formatage JSON (avant : `json_encode()` dupliqué partout)
- `sortUtils.js` centralise la logique de tri (avant : dupliquée dans 3 fichiers JS)

### KISS (Keep It Simple, Stupid)

- Architecture simple : pas de framework, pas d'ORM, pas de routing complexe
- Classes utilitaires légères et focalisées
- Pas de dépendances externes côté PHP (sauf FPDF pour les PDF)

## Gestion des secrets

Les credentials ne sont **jamais en dur** dans le code. Ils sont stockés dans `.env` :

```
DB_HOST=localhost
DB_NAME=gestion_materiel_db
DB_USER=root
DB_PASS=motdepasse_ici
```

Le fichier `.env` est dans `.gitignore`. Un template `.env.example` est fourni.

## Logging & Monitoring

### Logs applicatifs

Les logs sont écrits dans `logs/app-YYYY-MM-DD.log` avec rotation quotidienne.

**Format** : `[timestamp] [LEVEL] [endpoint] message {contexte JSON}`

**Niveaux** : `DEBUG`, `INFO`, `WARNING`, `ERROR` (configurable via `LOG_LEVEL` dans `.env`)

**Exemple** :

```
[2026-03-05 14:30:00] [INFO] [addItem.php] Matériel ajouté {"type":"Casque","nom":"Audio Pro","nombre":3}
[2026-03-05 14:31:12] [ERROR] [deleteItem.php] Exception non gérée {"message":"SQLSTATE[...]"}
```

### Monitoring (Health Check)

Endpoint : `php/monitor.php`

Retourne en JSON :

- **database** : statut de la connexion BDD
- **disk** : espace disque utilisé/libre
- **errors** : nombre d'erreurs dans la dernière heure
- **logs** : taille du fichier de log du jour
- **alerts** : alertes si trop d'erreurs détectées

### Alerting

Le monitoring génère des alertes automatiques si :

- Plus de 10 erreurs détectées dans la dernière heure → statut `degraded`
- Espace disque > 90% utilisé → warning

## Statelessness

L'application est **sans état** (stateless) :

- Aucune session PHP (`$_SESSION`) utilisée
- Chaque requête HTTP est indépendante
- La connexion BDD est créée par requête, pas partagée entre requêtes
- Pas de fichiers temporaires côté serveur (sauf logs)
- Toutes les données transitent via l'API JSON

Cela facilite le **passage à l'échelle** : l'application peut être déployée derrière un load balancer sans problème de synchronisation d'état.

## API Endpoints

| Méthode | Endpoint                                    | Description                 |
| ------- | ------------------------------------------- | --------------------------- |
| GET     | `php/getAllItems.php`                  | Liste tous les matériels    |
| GET     | `php/getItemDetails.php?code_barre=X` | Détails d'un matériel       |
| POST    | `php/addItem.php`                      | Ajouter du matériel         |
| POST    | `php/updateItem.php`                   | Modifier un matériel        |
| POST    | `php/deleteItem.php`                   | Supprimer un matériel       |
| GET     | `php/getAllBoxes.php`                   | Liste toutes les caisses    |
| GET     | `php/getBoxDetails.php?nom=X`          | Détails d'une caisse        |
| POST    | `php/addBox.php`                        | Ajouter une caisse          |
| POST    | `php/updateBox.php`                     | Modifier une caisse         |
| POST    | `php/deleteBox.php`                     | Supprimer une caisse        |
| GET     | `php/searchUniversal.php?type=X&query=Y`   | Recherche universelle       |
| GET     | `php/searchBarcodes.php?query=X`       | Recherche codes-barres      |
| GET     | `php/getAvailableObjects.php`             | Objets disponibles          |
| GET     | `php/getUsers.php`                  | Liste des utilisateurs      |
| GET     | `php/checkBarcode.php?code_barre=X`        | Vérifier unicité code-barre |
| GET     | `php/monitor.php`                           | Health check                |

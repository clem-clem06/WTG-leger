# WorkTogether — Site client (WTG-leger)

Plateforme web de location d'unités serveur en datacenter : catalogue d'offres,
panier, paiement carte + virement bancaire, espace client avec suivi des serveurs loués
et API REST.

> **Écosystème WorkTogether** — ce dépôt est le **client léger** (site web Symfony, destiné
> aux clients). Il partage sa base de données avec le **client lourd**
> [`WTG-lourd`](https://github.com/clem-clem06/WTG-lourd) (application JavaFX d'administration
> pour le personnel : admin, comptable, technicien).

---

## Stack technique

| Couche    | Techno                            |
|-----------|-----------------------------------|
| Backend   | PHP 8.4 + Symfony 8.0             |
| ORM       | Doctrine ORM 3.6 + Migrations     |
| BDD       | MySQL 8                          |
| Frontend  | Twig + Bootstrap 5 + AssetMapper  |
| JS        | Stimulus + Turbo                  |
| Sécurité  | Argon2id, firewalls session + API Bearer |
| Qualité   | PHPUnit, PHPStan, PHP-CS-Fixer    |

---

## Prérequis

- PHP ≥ 8.4 + Composer
- MySQL 8
- Symfony CLI (optionnel, pour `symfony serve`)
- **Pas de Node.js** — AssetMapper gère les assets nativement

---

## Installation

```bash
git clone https://github.com/clem-clem06/WTG-leger
cd WTG-leger
composer install
cp .env .env.local      # puis renseigner les secrets (voir plus bas)
```

Création de la base + données de démo :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony serve            # ou configurer un vhost
```

→ Application sur **http://127.0.0.1:8000**.

### Comptes de démonstration (fixtures)

| Email                | Mot de passe   | Rôle            |
|----------------------|----------------|-----------------|
| admin@wtg.fr         | Admin123!      | ROLE_ADMIN      |
| comptable@wtg.fr     | Comptable123!  | ROLE_COMPTABLE  |
| technicien@wtg.fr    | Technicien123! | ROLE_TECHNICIEN |
| client@hotmail.fr    | Client123!     | ROLE_CLIENT     |

> Seul `ROLE_CLIENT` peut acheter et accéder à l'espace client. Les autres rôles sont
> destinés au client lourd ; sur le site, ils voient une page de blocage.

---

## Variables d'environnement

À définir dans `.env.local` (jamais commité) :

| Variable            | Description                                   |
|---------------------|-----------------------------------------------|
| `DATABASE_URL`      | Connexion MySQL                               |
| `APP_SECRET`        | Clé de session Symfony                        |
| `API_TOKEN_CLIENT`  | Token Bearer du compte client (API REST)      |
| `CORS_ALLOW_ORIGIN` | Regex des origines autorisées (CORS)          |

---

## Architecture

```
src/
├── Controller/     # Home, Cart, Checkout, Customer, Offer, Api
├── Entity/         # User, Offre, Baie, Unite, Cart, CartItem,
│                   # Order, OrderItem, Payment, Card, Intervention
├── Service/        # CartService, CheckoutService, PaymentService, RegistrationService
├── Repository/     # Requêtes Doctrine personnalisées (verrouillage SQL, etc.)
├── Security/       # ApiTokenHandler (authentification Bearer)
├── Enum/           # Constantes de statuts/états (PaymentStatus, OrderStatus, UniteEtat)
├── Command/        # CleanVirementsCommand (annule les virements expirés > 14 j)
└── DataFixtures/   # 4 offres, 30 baies (× 42 unités = 1260), 4 utilisateurs

templates/          # Vues Twig par domaine (home, cart, checkout, customer…)
assets/styles/      # Design system CSS (app.css + un fichier par page)
docs/               # MCD / MLD (PlantUML + PNG)
tests/              # PHPUnit (unitaires + fonctionnels)
```

---

## Rôles & Sécurité

| Rôle              | Accès                                                  |
|-------------------|--------------------------------------------------------|
| `ROLE_CLIENT`     | Panier, paiement, espace client, API REST              |
| `ROLE_COMPTABLE`  | Back-office (client lourd)                             |
| `ROLE_TECHNICIEN` | Interventions / maintenance (client lourd)             |
| `ROLE_ADMIN`      | Accès complet                                          |

Deux firewalls Symfony :

- **`api`** — *stateless*, authentification par **Bearer token**
  (`Authorization: Bearer <token>`) via `ApiTokenHandler`.
- **`main`** — session PHP, formulaire de login, remember-me 24 h.

Mots de passe hachés en **Argon2id**. Le `apiToken` est exclu de la sérialisation de session.

---

## Routes principales

Les **verbes HTTP sont respectés** : `GET` pour la lecture (sans effet de bord),
`POST` pour toute action qui modifie l'état (panier, paiement).

**Lecture / affichage (`GET`)**

| URL                       | Accès                  | Rôle                  |
|---------------------------|------------------------|-----------------------|
| `/`                       | Accueil + catalogue    | Public                |
| `/offer/{id}`             | Détail d'une offre     | ROLE_CLIENT           |
| `/cart`                   | Affichage du panier    | ROLE_CLIENT (session) |
| `/checkout`               | Formulaire de paiement | ROLE_CLIENT (session) |
| `/customer`              | Espace client          | ROLE_CLIENT (session) |
| `/customer/unites.json`   | Unités en JSON         | ROLE_CLIENT (session) |
| `/api/unites`             | API REST des unités    | ROLE_CLIENT (Bearer)  |
| `/login` · `/register`    | Connexion / inscription| Public / ROLE_CLIENT  |

**Actions / modifications (`POST`, protégées par token CSRF)**

| URL                          | Action                       |
|------------------------------|------------------------------|
| `/cart/add/{id}`             | Ajouter une offre au panier  |
| `/cart/remove/{id}`          | Retirer une ligne            |
| `/cart/increase/{id}`        | Quantité +1                  |
| `/cart/decrease/{id}`        | Quantité −1                  |
| `/cart/duree/{id}/{action}`  | Modifier la durée            |
| `/checkout`                  | Valider le paiement (carte)  |
| `/checkout/virement`         | Commander par virement       |

> `/api/unites` est en lecture seule → `GET` est le bon verbe. Les écritures passent
> par des formulaires Twig (HTML ne supporte que `GET`/`POST`), d'où le `POST` systématique
> pour les mutations.

---

## API REST

Endpoint *stateless* protégé par token Bearer :

```http
GET /api/unites
Authorization: Bearer <API_TOKEN_CLIENT>
```

Retourne les unités louées par l'utilisateur authentifié :

```json
[
  {
    "id": 1,
    "numero": "U01",
    "etat": "OK",
    "baie": "B001",
    "dateFinLocation": "01/03/2027"
  }
]
```

> `/customer/unites.json` renvoie les mêmes données mais via la **session** (navigable
> directement dans le navigateur une fois connecté).

---

## Tests & qualité

```bash
# Toute la suite (nécessite la base de test)
php bin/phpunit

# Unitaires seulement (sans base de données)
php bin/phpunit tests/Service tests/Entity tests/Security

# Analyse statique & style
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run
```

Base de test (une fois) :

```bash
php bin/console --env=test doctrine:database:create --if-not-exists
php bin/console --env=test doctrine:migrations:migrate --no-interaction
```

---

## Modèle de données

Les diagrammes **MCD** et **MLD** sont dans `docs/` (sources PlantUML `.puml` + exports `.png`).

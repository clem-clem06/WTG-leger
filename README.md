# WorkTogether — Plateforme de location de baies datacenter

SaaS de location d'unités serveur en datacenter. Tarification multi-tiers, panier d'achat, paiement CB + virement bancaire, espace client avec suivi des serveurs loués.

---

## Stack technique

| Couche   | Techno                          |
|----------|---------------------------------|
| Backend  | PHP 8.4 + Symfony 8.0           |
| ORM      | Doctrine 3.6 + Migrations       |
| BDD      | MySQL 8                         |
| Frontend | Twig + Bootstrap 5 + AssetMapper |
| JS       | Stimulus + Turbo                |

---

## Prérequis

- PHP ≥ 8.4 + Composer
- MySQL 8
- Symfony CLI (optionnel)
- **Pas de Node.js** — AssetMapper est natif à Symfony

---

## Installation

```bash
git clone https://github.com/clem-clem06/WTG-leger
cd WTG-leger
composer install
cp .env .env.local
```

Configurer `.env.local` (voir section Variables d'environnement), puis :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load
symfony serve
```

L'application est accessible sur `http://127.0.0.1:8000`.

Les fixtures créent automatiquement 3 comptes :

| Email               | Mot de passe | Rôle          |
|---------------------|--------------|---------------|
| admin@wtg.fr        | password     | ROLE_ADMIN    |
| comptable@wtg.fr    | password     | ROLE_COMPTABLE|
| client@wtg.fr       | password     | ROLE_CLIENT   |

---

## Variables d'environnement

| Variable            | Description                              |
|---------------------|------------------------------------------|
| `DATABASE_URL`      | Connexion MySQL                          |
| `APP_SECRET`        | Clé de session Symfony                   |
| `API_TOKEN_CLIENT`  | Token Bearer pour l'API REST             |
| `CORS_ALLOW_ORIGIN` | Regex des origines autorisées (CORS)     |

---

## Architecture

```
src/
├── Controller/     # Home, Cart, Checkout, Customer, Offer, Api
├── Entity/         # User, Offre, Baie, Unite, Order, Payment, Card, Intervention…
├── Service/        # CartService, CheckoutService, PaymentService, RegistrationService
├── Repository/     # Requêtes Doctrine personnalisées
├── Security/       # ApiTokenHandler (authentification Bearer)
└── DataFixtures/   # 4 offres, 30 baies (× 42 unités), 3 utilisateurs

templates/          # Vues Twig par domaine (home, cart, checkout, customer…)
assets/styles/      # Design system CSS (app.css + un fichier par page)
```

---

## Rôles & Sécurité

| Rôle             | Accès                                        |
|------------------|----------------------------------------------|
| `ROLE_CLIENT`    | Panier, paiement, espace client, API         |
| `ROLE_COMPTABLE` | Lecture back-office                          |
| `ROLE_ADMIN`     | Accès complet                                |

Deux firewalls Symfony :

- **`api`** — stateless, authentification par Bearer token (`Authorization: Bearer <token>`)
- **`main`** — session PHP, formulaire de login, remember-me 24 h

Les administrateurs et comptables qui accèdent à la page d'accueil voient une page bloquée leur indiquant qu'ils ne peuvent pas effectuer d'achats.

---

## Routes principales

| URL              | Méthode   | Accès         |
|------------------|-----------|---------------|
| `/`              | GET       | Public        |
| `/offer/{id}`    | GET / POST| Public        |
| `/cart`          | GET / POST| ROLE_CLIENT   |
| `/checkout`      | GET / POST| ROLE_CLIENT   |
| `/customer`      | GET       | ROLE_CLIENT   |
| `/api/unites`    | GET       | ROLE_CLIENT (Bearer) |

---

## API REST

```http
GET /api/unites
Authorization: Bearer <API_TOKEN_CLIENT>
```

Retourne la liste des unités louées par l'utilisateur authentifié.

```json
[
  {
    "id": 12,
    "numero": "U-042",
    "etat": "OK",
    "baie": "B-07",
    "dateFinLocation": "2027-04-01"
  }
]
```

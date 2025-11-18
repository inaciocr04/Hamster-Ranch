# Hamster Ranch API

API REST Symfony pour la gestion d'un élevage de hamsters avec authentification JWT.

## Prérequis

-   PHP >= 8.2
-   Composer
-   MySQL/MariaDB ou PostgreSQL
-   OpenSSL (pour la génération des clés JWT)
-   Extensions PHP requises :
    -   ext-ctype
    -   ext-iconv
    -   ext-pdo
    -   ext-openssl

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/inaciocr04/Hamster-Ranch.git
cd hamster_ranch
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer la base de données

Créez un fichier `.env.local` à la racine du projet (ou modifiez le fichier `.env` existant) :

```env
DATABASE_URL="mysql://user:password@127.0.0.1:3306/hamster_ranch?charset=utf8mb4"
```

**Pour PostgreSQL :**

```env
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/hamster_ranch?serverVersion=15&charset=utf8"
```

Remplacez `user`, `password` et `hamster_ranch` par vos propres valeurs.

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
```

### 5. Exécuter les migrations

```bash
php bin/console doctrine:migrations:migrate
```

### 6. Configurer JWT

Générez les clés privée et publique pour JWT :

```bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
```

**Note :** Vous devrez entrer un mot de passe lors de la génération de la clé privée.

Ajoutez les variables d'environnement dans votre fichier `.env.local` :

```env
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=votre_mot_de_passe_ici
```

Remplacez `votre_mot_de_passe_ici` par le mot de passe que vous avez utilisé lors de la génération de la clé privée.

### 7. Démarrer le serveur de développement

```bash
symfony serve
```

L'API sera accessible à l'adresse : `http://127.0.0.1:8000/`

### 8. Démarrer avec des données

Faites cette commande pour avoir des données sinon les route marcherons pas

```bash
php bin/console doctrine:fixtures:load
```

## Utilisation de l'API

### Authentification

Toutes les routes (sauf `/api/login` et `/api/register`) nécessitent un token JWT dans l'en-tête `Authorization` :

```
Authorization: Bearer <votre_token_jwt>
```

### Routes disponibles

#### Authentification

**POST /api/login_check**

-   **Description :** Connexion et récupération du token JWT
-   **Body :**

```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

-   **Réponse 200 :**

```json
{
    "token": "eyJ0eXAiOiJKV1QiLCJhbGc..."
}
```

**POST /api/register**

-   **Description :** Création d'un nouveau compte utilisateur
-   **Body :**

```json
{
    "email": "user@example.com",
    "password": "password123"
}
```

-   **Réponse 201 :**

```json
{
  "user": { ... },
  "hamsters": [ ... ]
}
```

#### Utilisateur

**GET /api/user**

-   **Description :** Récupère les informations de l'utilisateur connecté
-   **Authentification :** Requise (JWT)
-   **Réponse 200 :**

```json
{
  "user": {
    "id": 1,
    "email": "user@example.com",
    "gold": 500,
    "hamsters": [ ... ]
  }
}
```

**DELETE /api/delete/{id}**

-   **Description :** Supprime un utilisateur (admin uniquement)
-   **Authentification :** Requise (JWT + ROLE_ADMIN)
-   **Réponse 200 :**

```json
{
    "message": "Utilisateur et ses hamsters supprimés avec succès"
}
```

#### Hamsters

**GET /api/hamsters**

-   **Description :** Récupère tous les hamsters de l'utilisateur connecté
-   **Authentification :** Requise (JWT)
-   **Réponse 200 :**

```json
{
    "hamsters": [
        {
            "id": 1,
            "name": "Hamster1",
            "age": 10,
            "hunger": 80,
            "genre": "m",
            "active": true
        }
    ]
}
```

**GET /api/hamsters/{id}**

-   **Description :** Récupère un hamster spécifique
-   **Authentification :** Requise (JWT)
-   **Réponse 200 :**

```json
{
    "hamster": {
        "id": 1,
        "name": "Hamster1",
        "age": 10,
        "hunger": 80,
        "genre": "m",
        "active": true
    }
}
```

**POST /api/hamsters/reproduce**

-   **Description :** Fait se reproduire deux hamsters
-   **Authentification :** Requise (JWT)
-   **Body :**

```json
{
    "idHamster1": 1,
    "idHamster2": 2
}
```

-   **Réponse 201 :**

```json
{
    "hamster": {
        "id": 3,
        "name": "NouveauHamster",
        "age": 0,
        "hunger": 100,
        "genre": "f",
        "active": true
    }
}
```

**POST /api/hamsters/{id}/feed**

-   **Description :** Nourrit un hamster (coût en or)
-   **Authentification :** Requise (JWT)
-   **Réponse 200 :**

```json
{
  "hamster": {
    "id": 1,
    "name": "Hamster1",
    "hunger": 100,
    ...
  }
}
```

**POST /api/hamsters/{id}/sell**

-   **Description :** Vend un hamster (gain de 300 gold)
-   **Authentification :** Requise (JWT)
-   **Réponse 200 :**

```json
{
    "message": "Hamster vendu avec succès pour 300 gold",
    "gold": 800
}
```

**PUT /api/hamsters/{id}/rename**

-   **Description :** Renomme un hamster
-   **Authentification :** Requise (JWT)
-   **Body :**

```json
{
    "name": "NouveauNom"
}
```

-   **Réponse 200 :**

```json
{
  "hamster": {
    "id": 1,
    "name": "NouveauNom",
    ...
  }
}
```

**POST /api/hamster/sleep/{nbDays}**

-   **Description :** Fait vieillir tous les hamsters de l'utilisateur
-   **Authentification :** Requise (JWT)
-   **Réponse 200 :**

```json
{
  "message": "Tous les hamsters ont vieilli de 5 jour(s)",
  "hamsters": [ ... ]
}
```

### Codes de réponse HTTP

-   **200** : Succès
-   **201** : Créé avec succès
-   **400** : Requête invalide (données manquantes ou incorrectes)
-   **401** : Non authentifié (token JWT manquant ou invalide)
-   **403** : Accès refusé (permissions insuffisantes)
-   **404** : Ressource introuvable
-   **500** : Erreur serveur

## Structure du projet

```
hamster_ranch/
├── config/              # Configuration Symfony
│   ├── jwt/            # Clés JWT (à générer)
│   └── packages/       # Configuration des bundles
├── migrations/         # Migrations Doctrine
├── public/            # Point d'entrée web
├── src/
│   ├── Controller/    # Contrôleurs API
│   ├── Entity/        # Entités Doctrine
│   ├── Repository/    # Repositories Doctrine
│   └── Service/       # Services métier
└── var/               # Cache et logs
```

## Commandes utiles

```bash
# Créer une nouvelle migration
php bin/console make:migration

# Exécuter les migrations
php bin/console doctrine:migrations:migrate

# Charger les fixtures (si disponibles)
php bin/console doctrine:fixtures:load
```

## Sécurité

-   Toutes les routes API (sauf `/api/login` et `/api/register`) nécessitent une authentification JWT
-   Les mots de passe sont hashés avec l'algorithme configuré dans Symfony
-   La route `/api/delete/{id}` nécessite le rôle `ROLE_ADMIN`
-   Chaque utilisateur ne peut accéder qu'à ses propres hamsters


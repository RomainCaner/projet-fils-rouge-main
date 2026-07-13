# Manuel utilisateur — Plateforme Cyna

**Projet fil rouge — Version 1.0**

Ce manuel s'adresse à deux publics :

- la **partie A** couvre le site web e-commerce (clients et visiteurs) ;
- la **partie B** couvre le back-office web et l'application Java Swing
  (administrateurs).

---

# Partie A — Client / Visiteur (site web)

## A.1 Naviguer sur le site

Le site est **responsive** : il s'adapte automatiquement à votre ordinateur,
tablette ou smartphone. En haut de chaque page se trouvent :

- le **logo** (retour à l'accueil) ;
- la **barre de recherche** ;
- l'**icône panier** (avec un indicateur si des articles sont présents) ;
- le **menu** (☰ sur mobile) donnant accès aux catégories, à votre compte, aux
  pages légales et au **sélecteur de langue** (Français / English).

## A.2 Découvrir les services

1. Depuis l'accueil, parcourez la **grille des catégories** (SOC, EDR, XDR…) ou
   la section **« Top produits du moment »**.
2. Cliquez sur une catégorie pour afficher son **catalogue**. Les services
   disponibles apparaissent en premier ; les services en maintenance sont
   grisés et signalés par la mention « Indisponible ».
3. Cliquez sur un service pour ouvrir sa **fiche détaillée** : illustrations,
   description, caractéristiques techniques, prix (mensuel/annuel) et services
   similaires.

## A.3 Rechercher un service

Ouvrez la page **Recherche** et affinez avec les facettes :

- texte (titre / description) ;
- catégorie(s) ;
- fourchette de prix ;
- « uniquement les services disponibles ».

Vous pouvez trier les résultats par **prix**, **nouveauté** ou **disponibilité**,
en ordre croissant ou décroissant.

## A.4 Créer un compte

1. Menu → **S'inscrire**.
2. Renseignez votre nom, votre e-mail et un mot de passe respectant les règles de
   sécurité : **au moins 8 caractères, une majuscule, une minuscule, un chiffre
   et un caractère spécial**.
3. Validez : un **e-mail de confirmation** vous est envoyé.
4. Cliquez sur le lien reçu (valable 24 h) pour **activer votre compte**. Vous
   êtes alors connecté automatiquement.

> Mot de passe oublié ? Sur la page de connexion, cliquez sur **« Mot de passe
> oublié »**, saisissez votre e-mail et suivez le lien de réinitialisation reçu.

## A.5 Commander un service

1. Sur une fiche service, choisissez la **périodicité** (mensuel/annuel) et la
   **quantité**, puis **Ajouter au panier**.
2. Ouvrez le **panier** : le total se met à jour automatiquement. Vous pouvez
   modifier les quantités ou retirer un service.
3. Cliquez sur **Passer à la caisse**. Vous pouvez vous connecter, créer un
   compte ou continuer en **invité**.
4. Renseignez votre **adresse de facturation** (ou choisissez-en une
   enregistrée).
5. Saisissez vos **informations de paiement** (paiement sécurisé Stripe).
   > En environnement de test, utilisez la carte `4242 4242 4242 4242`, une date
   > future et n'importe quel CVV.
6. Vérifiez le **récapitulatif** puis **Confirmer l'achat**. Vous recevez un
   **e-mail de confirmation** et pouvez **télécharger votre facture PDF**.

## A.6 Gérer son compte

Depuis **Mon compte**, vous accédez à :

| Rubrique | Actions possibles |
|----------|-------------------|
| Paramètres | Modifier nom, e-mail (avec re-validation), mot de passe (mot de passe actuel requis) |
| Carnet d'adresses | Ajouter, modifier, supprimer une adresse, définir celle par défaut |
| Moyens de paiement | Ajouter/supprimer une carte, définir la carte par défaut |
| Abonnements | Renouveler, mettre à jour ou résilier un abonnement |
| Commandes | Consulter l'historique (groupé par année), voir le détail, télécharger les factures PDF |

---

# Partie B — Administrateur

## B.1 Back-office web

### B.1.1 Connexion sécurisée (2FA)

1. Accédez à **`/admin/connexion`**.
2. Saisissez vos identifiants administrateur.
3. Saisissez le **code à 6 chiffres** généré par votre application
   d'authentification (Google Authenticator / Authy).
   > **Premier accès :** scannez le QR code affiché pour enrôler votre compte
   > dans l'application d'authentification, puis saisissez le code.

Compte de démonstration : `admin@cyna-it.fr` / `Admin@1234` (2FA à configurer au
premier accès).

### B.1.2 Tableau de bord

Le tableau de bord présente :

- l'**histogramme des ventes** (par jour sur 7 jours, ou par semaine sur 5
  semaines) ;
- l'**histogramme des paniers moyens par catégorie** ;
- le **camembert** de répartition des ventes par catégorie ;
- les **indicateurs clés** (KPI).

### B.1.3 Gestion des ressources

| Ressource | Fonctions |
|-----------|-----------|
| Produits | Lister (tri par colonne, recherche), créer, éditer, supprimer, **suppression multiple** |
| Catégories | Créer, éditer, supprimer, réordonner |
| Commandes | Lister, consulter le détail, **changer le statut** |
| Utilisateurs | Consulter la liste |
| Page d'accueil | Modifier le **carrousel** (images, textes, ordre) et le texte fixe |
| Messages | Consulter les messages du formulaire de contact, changer leur statut |

## B.2 Application Java Swing (CynaAdminPanel)

L'application lourde sert à la **gestion interne des stocks, des commandes et des
installations**. Elle partage la même base MySQL que le site web.

### B.2.1 Lancement

- **Windows** : double-cliquer sur `CynaAdminPanel.exe` (ou lancer
  `java -jar CynaAdminPanel.jar`).
- Prérequis : Java 17+ et un accès réseau à la base MySQL (configuré dans
  `application.properties`).

### B.2.2 Connexion

Saisissez vos identifiants administrateur. Si la double authentification est
activée sur votre compte, saisissez ensuite le **code TOTP** — identique à celui
du back-office web.

### B.2.3 Modules disponibles

| Module | Description |
|--------|-------------|
| Tableau de bord | Graphiques de synthèse (JFreeChart), alertes de stock bas |
| Produits | Tableau triable, ajout/modification, import d'image, validation de saisie |
| Commandes | Vue des commandes à traiter, changement de statut |
| Installations | Suivi du déploiement chez le client (PENDING → IN PROGRESS → INSTALLED) |
| Paramètres | Réglages de l'application |

---

## Annexe — Résolution des problèmes courants

| Problème | Solution |
|----------|----------|
| Je ne reçois pas l'e-mail de confirmation | Vérifiez les indésirables ; en local, consultez l'interface Mailpit (`http://localhost:8025`) |
| « Mot de passe trop faible » | Respectez les 4 critères (8 car., maj, min, chiffre, spécial) |
| Impossible de finaliser la commande | Un service du panier est indisponible : retirez-le avant de payer |
| Accès admin refusé | Vérifiez vos identifiants **et** votre code 2FA (horloge du téléphone à l'heure) |
| L'application Java ne démarre pas | Vérifiez la version de Java (17+) et la connexion à la base MySQL |

# Pipeline d'intégration et de déploiement continus (CI/CD)

**Projet fil rouge — Plateforme Cyna**
Version 1.0 — Bloc 3

Ce document décrit l'**automatisation** de la chaîne de livraison : intégration
continue (build + tests) et stratégie de déploiement continu. Il répond à l'axe
« Automatisation (CI/CD) » du Bloc 3.

---

## 1. Vue d'ensemble

```
  Développeur                GitHub                     Production (VM Oracle)
  ───────────                ──────                     ──────────────────────
  git push feature/*  ─────►  Déclencheur push/PR
                                    │
                                    ▼
                        ┌───────────────────────┐
                        │  CI — GitHub Actions   │
                        │  1. Lint PHP           │
                        │  2. PHPUnit (web)      │
                        │  3. JUnit (Java)       │
                        └───────────┬───────────┘
                                    │ vert
                        Revue de code + merge sur main
                                    │
                                    ▼
                        ┌───────────────────────┐
                        │  CD (semi-automatisé)  │
                        │  git pull + rebuild    │
                        │  docker compose up -d  │
                        └───────────────────────┘
```

## 2. Intégration continue (CI)

Le pipeline est défini dans [`.github/workflows/ci.yml`](../.github/workflows/ci.yml)
et se déclenche à chaque `push` et `pull_request` sur `main` et `develop`.

### 2.1 Job `php-tests` (site web)

| Étape | Action |
|-------|--------|
| Checkout | Récupération du code |
| Setup PHP 8.2 | Extensions `pdo`, `pdo_mysql`, `mbstring`, `curl`, `gd` + Composer |
| Lint | `php -l` sur `src`, `public`, `bootstrap`, `routes` |
| Install | `composer install` (dépendances de dev, dont PHPUnit) |
| Tests | `composer test` (suite PHPUnit) |

### 2.2 Job `java-tests` (application Swing)

| Étape | Action |
|-------|--------|
| Checkout | Récupération du code |
| Setup JDK 17 | Distribution Temurin, cache Maven |
| Build + tests | `mvn -B test` |

Les deux jobs s'exécutent **en parallèle** ; un échec de l'un fait échouer le
pipeline et bloque la fusion.

### 2.3 Règle de branche

Git Flow simplifié : `main` = production, `develop` = intégration, une branche
`feature/*` par ticket. **Aucune fusion sans CI verte + revue** par un autre
membre (protection de branche recommandée sur GitHub : *Require status checks to
pass before merging*).

## 3. Déploiement continu (CD)

Le déploiement cible une VM **Oracle Cloud Always Free** (ressources limitées),
d'où un CD **semi-automatisé** : déclenchement manuel après validation, exécution
automatisée par script. L'architecture Docker garantit l'iso-production.

### 3.1 Procédure de déploiement

```bash
# Sur la VM de production, depuis /opt/cyna
git pull origin main                 # récupère la version validée
docker compose pull                  # images de base à jour (CVE)
docker compose up -d --build         # reconstruction + redémarrage sans coupure prolongée
docker compose exec web php --version   # vérification post-déploiement
curl -fsS https://cyna.example.org/health   # health check
```

Détails d'installation et de configuration : [`DEPLOIEMENT_ORACLE.md`](DEPLOIEMENT_ORACLE.md).

### 3.2 Vers un CD entièrement automatisé (piste d'évolution)

Un job de déploiement peut être ajouté au pipeline, déclenché sur *push* de tag
de version, se connectant en SSH à la VM :

```yaml
# extrait indicatif — job à ajouter à ci.yml, déclenché sur tags v*
deploy:
  needs: [php-tests, java-tests]
  if: startsWith(github.ref, 'refs/tags/v')
  runs-on: ubuntu-latest
  steps:
    - name: Déploiement SSH
      uses: appleboy/ssh-action@v1
      with:
        host: ${{ secrets.DEPLOY_HOST }}
        username: ${{ secrets.DEPLOY_USER }}
        key: ${{ secrets.DEPLOY_SSH_KEY }}
        script: |
          cd /opt/cyna && git pull origin main
          docker compose up -d --build
```

> Les secrets (`DEPLOY_HOST`, `DEPLOY_SSH_KEY`…) sont stockés dans **GitHub
> Secrets**, jamais dans le dépôt.

### 3.3 Retour arrière (*rollback*)

En cas de régression détectée après déploiement :

```bash
git checkout <tag-precedent>      # ou le commit stable connu
docker compose up -d --build
```

Le volume MySQL étant persistant, un retour de code n'affecte pas les données ;
en cas de migration de schéma, restaurer la sauvegarde correspondante
(cf. [`INFRA_SAUVEGARDE_SSL.md`](INFRA_SAUVEGARDE_SSL.md)).

## 4. Démonstration en soutenance (axe CI/CD)

1. Ouvrir l'onglet **Actions** du dépôt GitHub : montrer l'historique des runs.
2. Faire un petit *commit* en direct sur une branche → montrer le pipeline se
   déclencher (lint + PHPUnit + JUnit).
3. Montrer un run **rouge** historique (test cassé) puis **vert** après correctif
   → prouver que la CI bloque bien la régression.
4. Sur la VM, dérouler la procédure de déploiement §3.1 et le *health check*.

---

## Références

- [Workflow CI](../.github/workflows/ci.yml)
- [DAT — Dossier d'Architecture Technique](DAT_Dossier_Architecture_Technique.md)
- [Déploiement Oracle Cloud](DEPLOIEMENT_ORACLE.md)
- [Cahier de recettes](CAHIER_DE_RECETTES.md)

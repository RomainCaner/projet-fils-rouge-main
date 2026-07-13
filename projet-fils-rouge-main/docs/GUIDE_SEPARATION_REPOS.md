# Guide — Séparation en deux dépôts Git distincts

La note de cadrage (§1.1, §4.4) et le cahier des charges (§XIX) imposent **deux
repositories Git distincts** :

1. **`cyna-web`** — site web e-commerce + back-office (dossier `Cyna/`) ;
2. **`cyna-admin-java`** — application lourde Java Swing (dossier `src/`, `pom.xml`).

Actuellement, les deux cohabitent dans le dépôt unique
`github.com/Inedra15/projet-fils-rouge`. Ce guide décrit comment les séparer
**en conservant l'historique des commits** de chaque partie.

> ⚠️ Sauvegardez d'abord une copie du dépôt (ou travaillez sur un clone dédié).
> `git filter-repo` réécrit l'historique de façon irréversible.

---

## Méthode recommandée — `git filter-repo` (historique préservé)

### Pré-requis

```bash
# Installer git-filter-repo (https://github.com/newren/git-filter-repo)
pip install git-filter-repo    # ou : brew install git-filter-repo
```

### 1. Créer le dépôt web (`cyna-web`)

```bash
git clone https://github.com/Inedra15/projet-fils-rouge.git cyna-web
cd cyna-web

# Ne conserver que le dossier Cyna/ et le remonter à la racine
git filter-repo --subdirectory-filter Cyna

# Pointer vers le nouveau dépôt distant (à créer au préalable sur GitHub)
git remote add origin https://github.com/<compte>/cyna-web.git
git push -u origin main
```

### 2. Créer le dépôt Java (`cyna-admin-java`)

```bash
git clone https://github.com/Inedra15/projet-fils-rouge.git cyna-admin-java
cd cyna-admin-java

# Conserver uniquement le code Java, le pom et les tests
git filter-repo \
  --path src/ \
  --path pom.xml \
  --path dependency-reduced-pom.xml \
  --path .github/    # workflow CI (à adapter au périmètre Java)

git remote add origin https://github.com/<compte>/cyna-admin-java.git
git push -u origin main
```

---

## Méthode alternative — sans réécriture d'historique

Si `git filter-repo` n'est pas disponible, créer deux dépôts neufs (l'historique
commun est perdu, mais chaque dépôt repart proprement) :

```bash
# Dépôt web
mkdir cyna-web && cp -r projet-fils-rouge/Cyna/* cyna-web/
cd cyna-web && git init && git add . && git commit -m "Init: site web Cyna"

# Dépôt Java
mkdir cyna-admin-java
cp -r projet-fils-rouge/src projet-fils-rouge/pom.xml cyna-admin-java/
cd cyna-admin-java && git init && git add . && git commit -m "Init: application Java Swing"
```

---

## Après la séparation

- **CI/CD** : le workflow `.github/workflows/ci.yml` actuel contient deux jobs
  (`php-tests`, `java-tests`). Dans chaque nouveau dépôt, ne conservez que le job
  pertinent et retirez le `working-directory: Cyna` devenu inutile côté web.
- **README** : chaque dépôt doit disposer de son propre README et de son guide
  d'installation.
- **DCT** : mettez à jour les liens croisés entre les deux dépôts (référencez les
  URLs GitHub plutôt que des chemins relatifs).
- **Chargé de projet** : consigner les deux URLs de dépôt dans le tableau des
  livrables du rapport de groupe.

---

## Convention de branches (rappel — note de cadrage §4.4)

| Branche | Rôle |
|---------|------|
| `main` | Production (protégée, fusion après revue uniquement) |
| `develop` | Intégration continue |
| `feature/<ticket>` | Une branche par fonctionnalité / ticket Jira |

Merge autorisé **uniquement après revue** par un autre membre de l'équipe.

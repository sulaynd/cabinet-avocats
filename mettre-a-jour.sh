#!/usr/bin/env bash
#
# Intègre une nouvelle archive de corrections dans un projet DÉJÀ installé
# (backend/ = vrai projet Laravel, frontend/ = vrai projet Angular).
#
# Ne touche QUE les fichiers fournis par le cabinet (app/, database/migrations,
# database/seeders, routes/api.php, resources/views côté Laravel ; core/,
# features/, app.routes.ts, app.config.ts, styles.scss, theme-overrides.scss
# côté Angular) — jamais vendor/, node_modules/, .env, composer.json,
# angular.json, etc. Sûr à relancer à chaque nouvelle archive reçue.
#
# Usage :
#   bash mettre-a-jour.sh chemin/vers/nouvelle-archive.zip
#
set -euo pipefail

if [ $# -lt 1 ]; then
  echo "Usage : bash mettre-a-jour.sh chemin/vers/archive.zip" >&2
  exit 1
fi

ARCHIVE="$1"
TMP_DIR=$(mktemp -d)

if [ ! -f "$ARCHIVE" ]; then
  echo "Erreur : archive introuvable : $ARCHIVE" >&2
  exit 1
fi

if [ ! -d "backend/app" ] || [ ! -d "frontend/src/app" ]; then
  echo "Erreur : lancez ce script depuis la racine du projet (celle qui contient déjà backend/ et frontend/ installés)." >&2
  exit 1
fi

echo "→ Extraction de l'archive dans un dossier temporaire..."
unzip -q "$ARCHIVE" -d "$TMP_DIR"

echo "→ Mise à jour du backend Laravel (app/, migrations, seeders, routes, vues)..."
cp -R "$TMP_DIR/backend/app/." backend/app/
cp -R "$TMP_DIR/backend/database/migrations/." backend/database/migrations/
cp -R "$TMP_DIR/backend/database/seeders/." backend/database/seeders/
cp "$TMP_DIR/backend/routes/api.php" backend/routes/api.php
mkdir -p backend/resources/views
cp -R "$TMP_DIR/backend/resources/views/." backend/resources/views/

echo "→ Mise à jour du frontend Angular (core/, features/, routes, config, styles)..."
cp -R "$TMP_DIR/frontend/src/app/core/." frontend/src/app/core/
cp -R "$TMP_DIR/frontend/src/app/features/." frontend/src/app/features/
cp "$TMP_DIR/frontend/src/app/app.routes.ts" frontend/src/app/app.routes.ts
cp "$TMP_DIR/frontend/src/app/app.config.ts" frontend/src/app/app.config.ts
if [ -f frontend/src/app/app.ts ] && [ -f "$TMP_DIR/frontend/src/app/app.ts" ]; then
  cp "$TMP_DIR/frontend/src/app/app.ts" frontend/src/app/app.ts
  cp "$TMP_DIR/frontend/src/app/app.html" frontend/src/app/app.html
  cp "$TMP_DIR/frontend/src/app/app.scss" frontend/src/app/app.scss
elif [ -f frontend/src/app/app.component.ts ] && [ -f "$TMP_DIR/frontend/src/app/app.ts" ]; then
  sed -e "s/export class App {}/export class AppComponent {}/" -e "s#\./app\.html#./app.component.html#" -e "s#\./app\.scss#./app.component.scss#" \
    "$TMP_DIR/frontend/src/app/app.ts" > frontend/src/app/app.component.ts
  cp "$TMP_DIR/frontend/src/app/app.html" frontend/src/app/app.component.html
  cp "$TMP_DIR/frontend/src/app/app.scss" frontend/src/app/app.component.scss
fi

# cabinet-styles.scss et theme-overrides.scss sont des fichiers séparés du
# styles.scss généré par Angular Material (voir install-frontend.sh) : on peut
# les remplacer entièrement sans jamais toucher au thème Material lui-même.
if [ -f "$TMP_DIR/frontend/src/cabinet-styles.scss" ]; then
  cp "$TMP_DIR/frontend/src/cabinet-styles.scss" frontend/src/cabinet-styles.scss
fi
if [ -f "$TMP_DIR/frontend/src/theme-overrides.scss" ]; then
  cp "$TMP_DIR/frontend/src/theme-overrides.scss" frontend/src/theme-overrides.scss
fi
# S'assurer que styles.scss importe bien les deux (idempotent, ne touche à
# rien d'autre dans le fichier — donc jamais destructeur pour le thème Material).
if [ -f frontend/src/styles.scss ]; then
  php -r '
  $file = $argv[1];
  $content = file_get_contents($file);
  $ajout = "";
  if (!str_contains($content, "cabinet-styles")) { $ajout .= "\n@import \x27./cabinet-styles\x27;"; }
  if (!str_contains($content, "theme-overrides")) { $ajout .= "\n@import \x27./theme-overrides\x27;"; }
  if ($ajout !== "") { file_put_contents($file, $content . $ajout . "\n"); }
  ' "frontend/src/styles.scss"
fi

echo "→ Vérification de la police Material Icons dans index.html..."
if [ -f frontend/src/index.html ]; then
  php -r '
  $file = $argv[1];
  $content = file_get_contents($file);
  if (!str_contains($content, "Material+Icons")) {
      $lien = "<link href=\"https://fonts.googleapis.com/icon?family=Material+Icons\" rel=\"stylesheet\">";
      $content = str_replace("</head>", "  " . $lien . "\n</head>", $content);
      file_put_contents($file, $content);
      echo "  police Material Icons ajoutée\n";
  }
  ' "frontend/src/index.html"
fi

echo "→ Mise à jour de la documentation (ARCHITECTURE.md, CONFIGURATION.md)..."
cp "$TMP_DIR/ARCHITECTURE.md" ./ARCHITECTURE.md 2>/dev/null || true
cp "$TMP_DIR/backend/CONFIGURATION.md" backend/CONFIGURATION.md 2>/dev/null || true

rm -rf "$TMP_DIR"

echo ""
echo "✅ Fichiers mis à jour. Il reste :"
echo "  1. cd backend && php artisan migrate   # applique les éventuelles nouvelles migrations (sans danger, ne rejoue pas les anciennes)"
echo "  2. Si de nouveaux packages composer/npm ont été mentionnés dans cette conversation,"
echo "     les installer manuellement (ce script ne touche pas composer.json/package.json)."
echo "  3. Rien à faire côté Angular si 'ng serve' tourne déjà (rechargement à chaud automatique)."
echo "     Sinon : cd frontend && ng serve"
echo ""
echo "⚠️  Si vous aviez modifié à la main un fichier dans backend/app/, backend/routes/api.php,"
echo "   ou frontend/src/app/(core|features)/, ce script l'a écrasé. Vérifiez votre 'git diff'"
echo "   (ou une sauvegarde) si vous aviez du code personnalisé à ces endroits."

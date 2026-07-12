#!/usr/bin/env bash
#
# Installe le frontend Angular du cabinet d'avocats :
#   1. crée un projet Angular neuf (ng new --standalone --routing)
#   2. installe Angular Material avec le thème du cabinet (encre/laiton)
#   3. copie par-dessus les fichiers fournis (core/, features/, routes, config, styles)
#
# À exécuter depuis la racine du dossier dézippé (celui qui contient déjà
# backend/, frontend/, ARCHITECTURE.md...) :
#
#   bash install-frontend.sh
#
set -euo pipefail

FOURNI="frontend"
FOURNI_TMP="frontend-fourni"
CIBLE="frontend"

if [ ! -d "$FOURNI" ]; then
  echo "Erreur : dossier '$FOURNI' introuvable. Lancez ce script depuis la racine du projet dézippé." >&2
  exit 1
fi

if [ -f "$FOURNI/angular.json" ]; then
  echo "Erreur : '$FOURNI' contient déjà un projet Angular complet (angular.json trouvé)." >&2
  echo "Rien à faire, ou supprimez-le manuellement si vous voulez repartir de zéro." >&2
  exit 1
fi

if ! command -v ng >/dev/null 2>&1; then
  echo "Erreur : Angular CLI ('ng') n'est pas installé." >&2
  echo "Installez-le d'abord :  npm install -g @angular/cli" >&2
  exit 1
fi

echo "→ Mise de côté des fichiers fournis ($FOURNI → $FOURNI_TMP)..."
rm -rf "$FOURNI_TMP"
mv "$FOURNI" "$FOURNI_TMP"

echo "→ Création d'un projet Angular neuf dans ./$CIBLE..."
echo "  (si l'assistant demande le SSR/SSG : répondez No — ce scaffold ne le configure pas)"
ng new "$CIBLE" --standalone --routing --style=scss --skip-git --skip-install

echo "→ Installation des dépendances npm..."
(cd "$CIBLE" && npm install)

echo "→ Ajout d'Angular Material (répondez : Custom (thème personnalisé) / Typography = Yes / Animations = Yes)..."
(cd "$CIBLE" && ng add @angular/material)

echo "→ Copie des fichiers du cabinet dans le projet Angular..."
mkdir -p "$CIBLE/src/app/core" "$CIBLE/src/app/features" "$CIBLE/src/environments"
cp -R "$FOURNI_TMP/src/app/core/." "$CIBLE/src/app/core/"
cp -R "$FOURNI_TMP/src/app/features/." "$CIBLE/src/app/features/"
cp "$FOURNI_TMP/src/app/app.routes.ts" "$CIBLE/src/app/app.routes.ts"
cp "$FOURNI_TMP/src/app/app.config.ts" "$CIBLE/src/app/app.config.ts"
# Racine app.* : remplace la page de bienvenue par défaut de "ng new" (logo
# Angular + "Hello, {{title}}") par un simple <router-outlet>. Angular a changé
# de convention de nommage selon les versions du CLI : projets récents =
# app.ts/app.html/app.scss (sans suffixe "component"), plus anciens =
# app.component.ts/html/scss. On détecte celle réellement générée.
if [ -f "$CIBLE/src/app/app.ts" ]; then
  cp "$FOURNI_TMP/src/app/app.ts" "$CIBLE/src/app/app.ts"
  cp "$FOURNI_TMP/src/app/app.html" "$CIBLE/src/app/app.html"
  cp "$FOURNI_TMP/src/app/app.scss" "$CIBLE/src/app/app.scss"
  echo "  convention détectée : app.ts (récente)"
elif [ -f "$CIBLE/src/app/app.component.ts" ]; then
  sed -e "s/export class App {}/export class AppComponent {}/" -e "s#\./app\.html#./app.component.html#" -e "s#\./app\.scss#./app.component.scss#" \
    "$FOURNI_TMP/src/app/app.ts" > "$CIBLE/src/app/app.component.ts"
  cp "$FOURNI_TMP/src/app/app.html" "$CIBLE/src/app/app.component.html"
  cp "$FOURNI_TMP/src/app/app.scss" "$CIBLE/src/app/app.component.scss"
  echo "  convention détectée : app.component.ts (plus ancienne)"
fi
cp -R "$FOURNI_TMP/src/environments/." "$CIBLE/src/environments/"

echo "→ Ajout de la police Material Icons dans index.html (nécessaire pour tous les <mat-icon>)..."
php -r '
$file = $argv[1] . "/src/index.html";
$content = file_get_contents($file);
$lien = "<link href=\"https://fonts.googleapis.com/icon?family=Material+Icons\" rel=\"stylesheet\">";
if (!str_contains($content, "Material+Icons")) {
    $content = str_replace("</head>", "  " . $lien . "\n</head>", $content);
    file_put_contents($file, $content);
    echo "  police Material Icons ajoutée à index.html\n";
} else {
    echo "  déjà présente, rien à faire\n";
}
' "$CIBLE"

echo "→ Ajout de notre feuille de style et du thème visuel du cabinet (encre/laiton)..."
# Fichiers séparés (jamais fusionnés dans styles.scss) : une future mise à jour
# via mettre-a-jour.sh peut donc les remplacer sans jamais toucher au thème
# Material généré par `ng add` dans styles.scss lui-même.
cp "$FOURNI_TMP/src/cabinet-styles.scss" "$CIBLE/src/cabinet-styles.scss"
cp "$FOURNI_TMP/src/theme-overrides.scss" "$CIBLE/src/theme-overrides.scss"
php -r '
$file = $argv[1] . "/src/styles.scss";
$content = file_get_contents($file);
$ajout = "";
if (!str_contains($content, "cabinet-styles")) { $ajout .= "\n@import \x27./cabinet-styles\x27;"; }
if (!str_contains($content, "theme-overrides")) { $ajout .= "\n@import \x27./theme-overrides\x27;"; }
if ($ajout !== "") {
    file_put_contents($file, $content . $ajout . "\n");
    echo "  imports ajoutés à styles.scss\n";
} else {
    echo "  déjà présents, rien à faire\n";
}
' "$CIBLE"

echo ""
echo "✅ Installation terminée. Le projet Angular complet est dans ./$CIBLE (Angular Material inclus)."
echo "   Les fichiers fournis originaux sont conservés dans ./$FOURNI_TMP (vous pouvez le supprimer ensuite)."
echo ""
echo "Étapes restantes :"
echo "  1. Vérifiez $CIBLE/src/environments/environment.ts : apiUrl doit pointer vers le backend Laravel"
echo "     (ex: http://localhost:8000/api)"
echo "  2. cd $CIBLE && ng serve"
echo "  3. Ouvrez http://localhost:4200 (le backend Laravel doit tourner en parallèle sur :8000)"

#!/usr/bin/env bash
#
# Installe le backend Laravel du cabinet d'avocats :
#   1. crée un projet Laravel neuf (composer create-project)
#   2. installe les packages nécessaires (Sanctum, dompdf)
#   3. copie par-dessus les fichiers fournis (app/, database/, routes/, resources/)
#   4. prépare le .env et génère la clé d'application
#
# À exécuter depuis la racine du dossier dézippé (celui qui contient déjà
# backend/, frontend/, ARCHITECTURE.md...) :
#
#   bash install-backend.sh
#
set -euo pipefail

FOURNI="backend"
FOURNI_TMP="backend-fourni"
CIBLE="backend"

if [ ! -d "$FOURNI" ]; then
  echo "Erreur : dossier '$FOURNI' introuvable. Lancez ce script depuis la racine du projet dézippé." >&2
  exit 1
fi

if [ -f "$FOURNI/composer.json" ]; then
  echo "Erreur : '$FOURNI' contient déjà un projet Laravel complet (composer.json trouvé)." >&2
  echo "Rien à faire, ou supprimez-le manuellement si vous voulez repartir de zéro." >&2
  exit 1
fi

echo "→ Mise de côté des fichiers fournis ($FOURNI → $FOURNI_TMP)..."
rm -rf "$FOURNI_TMP"
mv "$FOURNI" "$FOURNI_TMP"

echo "→ Création d'un projet Laravel neuf dans ./$CIBLE (téléchargement, peut prendre une minute)..."
composer create-project laravel/laravel "$CIBLE"

echo "→ Installation de Sanctum et dompdf..."
(cd "$CIBLE" && composer require laravel/sanctum barryvdh/laravel-dompdf)

echo "→ Copie des fichiers du cabinet dans le projet Laravel..."
cp -R "$FOURNI_TMP/app/." "$CIBLE/app/"
cp -R "$FOURNI_TMP/database/migrations/." "$CIBLE/database/migrations/"
cp -R "$FOURNI_TMP/database/seeders/." "$CIBLE/database/seeders/"
cp "$FOURNI_TMP/routes/api.php" "$CIBLE/routes/api.php"
mkdir -p "$CIBLE/resources/views"
cp -R "$FOURNI_TMP/resources/views/." "$CIBLE/resources/views/"

# Vérification explicite : mieux vaut s'arrêter ici avec un message clair que
# planter mystérieusement 2 étapes plus loin dans une commande artisan.
if [ ! -f "$CIBLE/routes/api.php" ]; then
  echo "Erreur : la copie de routes/api.php a échoué (fichier absent après cp)." >&2
  echo "Source attendue : $FOURNI_TMP/routes/api.php" >&2
  ls -la "$FOURNI_TMP/routes/" >&2
  exit 1
fi
if [ ! -d "$CIBLE/app/Http/Controllers/Api" ]; then
  echo "Erreur : la copie de app/ semble incomplète (app/Http/Controllers/Api introuvable)." >&2
  exit 1
fi
echo "  vérifié : routes/api.php et app/Http/Controllers/Api sont bien en place."

echo "→ Activation de routes/api.php dans bootstrap/app.php (absente par défaut sur un projet neuf)..."
# IMPORTANT : routes/api.php doit déjà avoir été copié (étape précédente) avant
# toute commande `php artisan`, sinon Laravel plante au démarrage en essayant
# de charger un fichier référencé qui n'existe pas encore.
php -r '
$file = $argv[1] . "/bootstrap/app.php";
$content = file_get_contents($file);
if (!str_contains($content, "api: __DIR__")) {
    $content = str_replace(
        "web: __DIR__.\x27/../routes/web.php\x27,",
        "web: __DIR__.\x27/../routes/web.php\x27,\n        api: __DIR__.\x27/../routes/api.php\x27,",
        $content
    );
    file_put_contents($file, $content);
    echo "  api: ajouté à bootstrap/app.php\n";
} else {
    echo "  déjà présent, rien à faire\n";
}
' "$CIBLE"

echo "→ Publication de la migration Sanctum (table personal_access_tokens)..."
(cd "$CIBLE" && php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag="sanctum-migrations" --force)

echo "→ Préparation du .env..."
cp "$FOURNI_TMP/.env.example" "$CIBLE/.env"
(cd "$CIBLE" && php artisan key:generate)

echo ""
echo "✅ Installation terminée. Le projet Laravel complet est dans ./$CIBLE"
echo "   Les fichiers fournis originaux sont conservés dans ./$FOURNI_TMP (vous pouvez le supprimer ensuite)."
echo ""
echo "Étapes restantes :"
echo "  1. Éditez $CIBLE/.env : au minimum DB_DATABASE/DB_USERNAME/DB_PASSWORD, et ADMIN_PASSWORD"
echo "  2. Enregistrez les middlewares 'role' et 'portail' dans $CIBLE/bootstrap/app.php"
echo "     (voir backend-fourni/CONFIGURATION.md §1 et §11 — copiez-collez l'extrait fourni)"
echo "  3. cd $CIBLE && php artisan migrate --seed   # --seed crée le compte admin (AdminUserSeeder)"
echo "  4. php artisan storage:link"
echo "  5. php artisan serve"
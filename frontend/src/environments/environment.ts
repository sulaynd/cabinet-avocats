/**
 * L'URL de l'API est détectée au moment de l'exécution (dans le navigateur),
 * plutôt que codée en dur — le build "production" d'Angular ne remplace pas
 * ce fichier (angular.json n'a pas de fileReplacements configuré), donc un
 * localhost codé en dur se serait retrouvé tel quel dans le build déployé,
 * cassant l'application pour chaque visiteur.
 *
 * Par défaut : même domaine que le frontend + "/api" (patron standard pour
 * un hébergement où Laravel et Angular partagent le même nom de domaine).
 * À ajuster ici si le backend est plutôt servi depuis un sous-domaine séparé
 * (ex: https://api.jca.203media.ca).
 */
function detecterApiUrl(): string {
  if (typeof window !== 'undefined' && window.location.hostname !== 'localhost') {
    return `${window.location.origin}/api`;
  }
  return 'http://localhost:8000/api';
}

export const environment = {
  production: false,
  apiUrl: detecterApiUrl(),
};

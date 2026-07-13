/** Forme publique (page d'accueil) : nom déjà dérivé du client côté serveur. */
export interface Temoignage {
  id: number;
  nom: string;
  texte: string;
}

/** Forme admin (écran de gestion) : inclut le client associé et le statut d'approbation. */
export interface TemoignageAdmin {
  id: number;
  client_id: number;
  client: { id: number; nom_complet: string; email: string } | null;
  texte: string;
  ordre: number;
  actif: boolean;
  created_at?: string;
}

export type Role = 'admin' | 'avocat' | 'assistant';

export interface Utilisateur {
  id: number;
  name: string;
  email: string;
  role: Role;
  phone?: string;
  taux_horaire_defaut?: number | null;
  dossiers_count?: number;
  created_at?: string;
  afficher_equipe_publique?: boolean;
  titre_public?: string | null;
  bio_publique?: string | null;
  photo_url?: string | null;
  doit_changer_mot_de_passe?: boolean;
}

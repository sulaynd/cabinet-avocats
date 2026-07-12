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
}

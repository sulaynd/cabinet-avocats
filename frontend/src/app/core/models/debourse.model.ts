export type CategorieDebourse = 'frais_cour' | 'deplacement' | 'photocopie' | 'autre';

export interface Debourse {
  id: number;
  dossier_id: number;
  user_id: number;
  user?: { id: number; name: string };
  categorie: CategorieDebourse;
  description: string;
  montant: number;
  date_debourse: string;
  facture_id: number | null;
}

export interface DeboursePayload {
  categorie: CategorieDebourse;
  description: string;
  montant: number;
  date_debourse: string;
}

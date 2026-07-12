export type TypeEcheance = 'audience' | 'delai_procedural' | 'rdv_client' | 'autre';
export type StatutEcheance = 'a_venir' | 'realisee' | 'annulee';

export interface Echeance {
  id: number;
  dossier_id: number;
  titre: string;
  type: TypeEcheance;
  date_heure: string;
  lieu?: string;
  statut: StatutEcheance;
  rappel_avant?: number;
}

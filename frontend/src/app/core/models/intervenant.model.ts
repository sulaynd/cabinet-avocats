export type FonctionIntervenant = 'avocat_adverse' | 'expert' | 'greffier' | 'huissier' | 'mediateur_arbitre' | 'notaire' | 'autre';

export interface Intervenant {
  id: number;
  dossier_id: number;
  nom: string;
  fonction: FonctionIntervenant;
  organisation: string | null;
  email: string | null;
  telephone: string | null;
  notes: string | null;
}

export interface IntervenantPayload {
  nom: string;
  fonction: FonctionIntervenant;
  organisation?: string | null;
  email?: string | null;
  telephone?: string | null;
  notes?: string | null;
}

export type ClientType = 'particulier' | 'entreprise';

export interface Client {
  id: number;
  type: ClientType;
  nom?: string;
  prenom?: string;
  raison_sociale?: string;
  siret?: string;
  email?: string;
  telephone?: string;
  adresse?: string;
  code_postal?: string;
  ville?: string;
  notes?: string;
  portail_active_le?: string | null;
  dossiers_count?: number;
  created_at?: string;
  updated_at?: string;
}

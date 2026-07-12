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
  created_at?: string;
  updated_at?: string;
}

import { Client } from './client.model';
import { Echeance } from './echeance.model';
import { DocumentFile } from './document.model';
import { Facture } from './facture.model';

export type TypeAffaire = 'civil' | 'penal' | 'commercial' | 'famille' | 'travail' | 'immobilier' | 'autre';
export type StatutDossier = 'ouvert' | 'en_cours' | 'en_attente' | 'clos' | 'archive';

export interface Avocat {
  id: number;
  name: string;
  email: string;
}

export interface Dossier {
  id: number;
  reference: string;
  client_id: number;
  avocat_id: number;
  assistant_id?: number | null;
  client?: Client;
  avocat?: Avocat;
  /** Assistant(e) traitant(e) du dossier, en plus de l'avocat responsable (optionnel). */
  assistant?: Avocat;
  titre: string;
  type_affaire: TypeAffaire;
  statut: StatutDossier;
  mode_facturation: 'horaire' | 'forfait';
  taux_horaire?: number | null;
  montant_forfait?: number | null;
  facturation_periodique?: boolean;
  frequence_facturation?: 'hebdomadaire' | 'mensuelle' | null;
  facturer_a_cloture?: boolean;
  date_ouverture?: string;
  date_cloture?: string;
  description?: string;
  echeances?: Echeance[];
  documents?: DocumentFile[];
  factures?: Facture[];
  created_at?: string;
  updated_at?: string;
}

import { Client } from './client.model';
import { Dossier } from './dossier.model';

export type StatutFacture = 'brouillon' | 'envoyee' | 'payee' | 'en_retard' | 'annulee';

export interface FactureLigne {
  id?: number;
  description: string;
  quantite: number;
  prix_unitaire: number;
  montant?: number;
}

export interface Facture {
  id: number;
  numero: string;
  dossier_id: number;
  client_id: number;
  client?: Client;
  dossier?: Dossier;
  date_emission: string;
  date_echeance?: string;
  montant_ht: number;
  taux_tva: number;
  montant_ttc: number;
  statut: StatutFacture;
  lignes?: FactureLigne[];
}

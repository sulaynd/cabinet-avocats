import { Avocat } from './dossier.model';

export type StatutRendezVous = 'demande' | 'confirme' | 'annule';

export interface RendezVous {
  id: number;
  nom: string;
  email: string;
  telephone?: string;
  motif: string;
  type_affaire: string;
  avocat_id: number | null;
  avocat?: Avocat | null;
  client_id?: number;
  date_heure: string;
  statut: StatutRendezVous;
}

import { Avocat } from './dossier.model';

export type StatutRendezVous = 'demande' | 'confirme' | 'annule';

export interface RendezVous {
  id: number;
  nom: string;
  email: string;
  telephone?: string;
  motif?: string;
  avocat_id: number;
  avocat?: Avocat;
  client_id?: number;
  date_heure: string;
  statut: StatutRendezVous;
}

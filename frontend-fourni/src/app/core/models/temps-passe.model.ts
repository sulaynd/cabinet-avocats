import { Avocat } from './dossier.model';

export interface TempsPasse {
  id: number;
  dossier_id: number;
  user_id: number;
  user?: Avocat;
  description?: string | null;
  demarre_a: string | null;
  termine_a: string | null;
  duree_secondes: number;
  facturable: boolean;
  taux_horaire_applique?: number | null;
  facture_id?: number | null;
}

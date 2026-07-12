export interface StatistiquesTableauDeBord {
  ca_total: number;
  ca_ce_mois: number;
  ca_cette_annee: number;
  factures_impayees: { montant: number; nombre: number };
  ca_par_avocat: { avocat: string; total: number }[];
  evolution_mensuelle: { mois: string; total: number }[];
  dossiers_par_statut: Record<string, number>;
  dossiers_par_type: { type: string; total: number }[];
  nombre_clients: number;
  audiences_a_venir: number;
  echeances_a_venir: number;
  rendez_vous: { a_venir: number; en_attente: number; total_confirmes: number; total_annules: number };
}

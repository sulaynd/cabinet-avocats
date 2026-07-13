export type TypeContrat = 'cdi' | 'cdd' | 'stage' | 'temps_partiel' | 'contractuel' | 'autre';

export interface OffreEmploi {
  id: number;
  titre: string;
  description: string;
  type_contrat: TypeContrat;
  lieu: string | null;
  date_limite: string | null;
  ordre: number;
  actif: boolean;
}

export interface OffreEmploiPayload {
  titre: string;
  description: string;
  type_contrat: TypeContrat;
  lieu?: string | null;
  date_limite?: string | null;
  ordre?: number;
  actif?: boolean;
}

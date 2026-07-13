export interface Actualite {
  id: number;
  titre: string;
  date: string;
  extrait: string;
  ordre: number;
  actif: boolean;
}

export interface ActualitePayload {
  titre: string;
  date: string;
  extrait: string;
  ordre?: number;
  actif?: boolean;
}

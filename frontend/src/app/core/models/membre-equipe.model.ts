export interface MembreEquipe {
  id: number;
  nom: string;
  titre: string | null;
  bio: string | null;
  photo_url: string | null;
  ordre: number;
  actif: boolean;
}

export interface MembreEquipePayload {
  nom: string;
  titre?: string | null;
  bio?: string | null;
  ordre?: number;
  actif?: boolean;
}

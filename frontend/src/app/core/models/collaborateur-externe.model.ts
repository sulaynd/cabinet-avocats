export interface CollaborateurExterne {
  id: number;
  nom: string;
  email: string;
  organisation: string | null;
  telephone: string | null;
  portail_active_le: string | null;
  doit_changer_mot_de_passe?: boolean;
}

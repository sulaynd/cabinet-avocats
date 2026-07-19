export type TypeDocument = 'contrat' | 'piece_procedure' | 'correspondance' | 'autre';

export interface DocumentFile {
  id: number;
  dossier_id: number;
  nom_original: string;
  chemin: string;
  type: TypeDocument;
  taille?: number;
  uploaded_by: number | null;
  necessite_signature?: boolean;
  signe_le?: string | null;
  signature_nom?: string | null;
  created_at?: string;
  partage_externe?: boolean;
  collaborateur_externe_id?: number | null;
}

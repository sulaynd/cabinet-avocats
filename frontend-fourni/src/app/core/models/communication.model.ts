export type TypeCommunication = 'appel' | 'email' | 'courrier' | 'reunion' | 'note';

export interface Communication {
  id: number;
  dossier_id: number;
  user_id: number;
  user?: { id: number; name: string };
  type: TypeCommunication;
  objet: string;
  contenu?: string | null;
  date_communication: string;
}

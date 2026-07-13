export interface ClientPortail {
  id: number;
  type: 'particulier' | 'entreprise';
  nom?: string;
  prenom?: string;
  raison_sociale?: string;
  nom_complet?: string;
  email: string;
  doit_changer_mot_de_passe?: boolean;
}

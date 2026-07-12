export interface ClientPortail {
  id: number;
  type: 'particulier' | 'entreprise';
  nom?: string;
  prenom?: string;
  raison_sociale?: string;
  email: string;
}

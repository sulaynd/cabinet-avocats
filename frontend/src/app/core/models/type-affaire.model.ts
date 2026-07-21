export interface SousCategorieAffaire {
  id: number;
  type_affaire_id: number;
  slug: string;
  libelle: string;
  actif: boolean;
  ordre: number;
}

export interface TypeAffaire {
  id: number;
  slug: string;
  libelle: string;
  actif: boolean;
  ordre: number;
  sous_categories?: SousCategorieAffaire[];
}

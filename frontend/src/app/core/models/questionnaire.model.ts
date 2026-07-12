export type TypeChampQuestionnaire = 'texte' | 'zone_texte' | 'choix' | 'case';

export interface ChampQuestionnaire {
  cle: string;
  label: string;
  type: TypeChampQuestionnaire;
  options?: string[];
  requis?: boolean;
}

export interface Questionnaire {
  id: number;
  nom: string;
  description?: string | null;
  type_affaire?: string | null;
  champs: ChampQuestionnaire[];
  actif: boolean;
}

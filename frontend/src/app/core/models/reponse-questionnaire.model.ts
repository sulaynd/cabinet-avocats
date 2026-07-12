import { Questionnaire } from './questionnaire.model';

export interface ReponseQuestionnaire {
  id: number;
  dossier_id: number;
  questionnaire_id: number;
  questionnaire?: Questionnaire;
  reponses?: Record<string, any> | null;
  envoye_le?: string | null;
  rempli_le?: string | null;
}

export interface QuestionnairePublicPayload {
  dossier_titre: string;
  client_nom: string;
  questionnaire: Questionnaire;
  deja_rempli: boolean;
  reponses_existantes: Record<string, any> | null;
}

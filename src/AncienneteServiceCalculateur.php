<?php

namespace Helip\EsahrAnciennete;

class AncienneteServiceCalculateur implements AncienneteCalculateurInterface
{
    /**
     * Calcule l'ancienneté d'un enseignant
     * 
     * @param Evenement[] $evenements
     * @return AncienneteService
     */
    public function calculer(array $evenements): AncienneteService
    {
        $anciennete = new AncienneteService();

        // trier les événements par date de début
        $evenements = AncienneteCalculateurHelper::trierEvenementsParDateDebut($evenements);

        foreach ($evenements as $evenement) {
          $this->calculerAnciennetePourEvenement($evenement, $anciennete);
        }

        return $anciennete;
    }


    /**
     * Met à jour l'objet AncienneteService 
     * avec le calcul de l'ancienneté pour un événement
     * 
     * @param Evenement $evenement
     * @param AncienneteService $anciennete
     */
    private function calculerAnciennetePourEvenement(Evenement $evenement, AncienneteService $anciennete): void
    {
        // Calcule le nombre de jours calendrier
        $joursCalendrier = AncienneteCalculateurHelper::getNombreJoursCalendrier(
            $evenement->getDateDebut(),
            $evenement->getDateFin()
        );

        $anciennete->resetCalculVariables();
        $anciennete->setAncienneteBrutePo($evenement->getAncienneteActuellePOEducatif(), Attribution::CAT_PERSONNEL_EDUCATIF);
        $anciennete->setAncienneteBrutePo($evenement->getAncienneteActuellePOAuxiliaire(), Attribution::CAT_PERSONNEL_AUXILIAIRE);
        
        $anciennete->setJoursCalendrier($joursCalendrier);

        foreach ($evenement->getAttributions() as $attribution) {

            if (!$attribution->getEstSubventionne()) {
                continue;
            }

            if ($attribution->getPeriodes() == 0) {
                continue;
            }

            $anciennete->add(
                $attribution->getCategorie(),
                $attribution->getChargeDecimal(),
                $attribution->getEstPO()
            );
        }
        // TODO catégorie pour ancienneté po
        $anciennete->calculerAnciennetes();
    }
}

<?php

namespace Helip\EsahrAnciennete;

class AncienneteServiceCalculateur implements AncienneteCalculateurInterface
{
    /**
     * Calcule l'ancienneté d'un enseignant
     * 
     * @param array $evenements
     * @param AncienneteInterface|null $anciennete Ancienneté déjà calculée, utile pour les 1200 jours PO
     * @return AncienneteService
     */
    public function calculer(array $evenements, ?AncienneteInterface $anciennete = null): AncienneteService
    {
        if ($anciennete === null) {
            $anciennete = new AncienneteService();
        } elseif (!($anciennete instanceof AncienneteService)) {
            throw new \InvalidArgumentException("Le paramètre fourni doit être une instance de AncienneteService.");
        }

        // trier les événements par date de début
        $evenements = AncienneteCalculateurHelper::trierEvenementsParDateDebut($evenements);

        foreach ($evenements as $evenement) {
            $anciennete->resetChargeDecimal(); // évite d'additionner les charges sur plusieurs evenements
            $this->calculerAnciennetePourEvenement($evenement, $anciennete);
        }

        return $anciennete;
    }


    private function calculerAnciennetePourEvenement(Evenement $evenement, AncienneteService $anciennete)
    {
        // groupe par catégorie
        $attributions = AncienneteCalculateurHelper::groupeParCategorieEtPo($evenement->getAttributions());

        // Calcule le nombre de jours calendrier
        $nbJoursCalendrier = AncienneteCalculateurHelper::getNombreJoursCalendrier(
            $evenement->getDateDebut(),
            $evenement->getDateFin()
        );

        // Boucle sur les attributions
        foreach ($attributions as $attribution) {

            // Si pas subventionnée, on passe
            if (!$attribution->getEstSubventionne()) {
                continue;
            }

            // si aucune période, on passe
            if ($attribution->getPeriodes() == 0) {
                continue;
            } 

            // si PO et pas titre requis, on passe
            if ($attribution->getEstPO() && !$attribution->getEstTitreRequis()) {
                continue;
            }

            $anciennete->add(
                $nbJoursCalendrier,
                $attribution->getCategorie(),
                $attribution->getChargeDecimal(),
                $attribution->getEstPO(),
                $evenement->getAncienneteActuellePO()
            );
        }
    }
}

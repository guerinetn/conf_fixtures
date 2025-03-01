<?php

namespace App\DataFixtures;

use App\Entity\Order;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixturesBasis extends Fixture
{

    /**
     * @inheritDoc
     */
    public function load(ObjectManager $manager): void
    {
        $candidate = $this->creeDemandeur("John", "Doe", "john.doe@test", "123456789", "Société", "MonM0tdepasse");
        $candidate->getUser()->setRoles([Role::ROLE_DEMANDEUR->name]);
        $commune = $this->communeRepository->findOneBy(['nom' => 'Bordeaux']);

        $param = [
            'demandeur' => [
                'tel' => '+33600000000',
                'company' => [
                    'email' => 'company@boite.mail',
                    'tel' => '+33600000000',
                    'formeJuridique' => 'autres',
                    'creationDate' => now('-3 year')->format(DATE_ATOM),
                ],
            ],
            'signataireFirstName' => 'signataire',
            'signataireLastName' => 'signataire',
            'signataireQuality' => 'signataire',
            'adresse' => [
                'adresse1' => 'numéro, nom de la voie',
                'adresse2' => 'complément adresse',
                'codePostal' => $commune->getCodePostaux()[0],
                'codeDepartement' => $commune->getDepartement()->getCode(),
                'ville' => $commune->getNom(),
            ],
            'qualities' => [$config['qualite']],
        ];
    }


}

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $objet = new Order();
        // Création de données
        $this->addReference('reference', $objet);
        $this->getReference('reference',$objet::class);
    }
    public function getDependencies(): array
    {
        return [
            AppFixturesBasis::class,
        ];
    }

}

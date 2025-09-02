# conf_fixtures

## Résumé

Les fixtures sont souvent utilisées pour générer des données, servant ainsi de fondation pour les tests fonctionnels et le développement.

Il est possible de générer les objets un a un.
Cependant, qu'en est-il d'une commande qui débute avec la sélection du panier et se termine par le statut 'terminé', incluant la génération de la facture et les différents envois d'e-mails ?

Pourquoi ne pas exploiter ce mécanisme pour initier nos objets et les faire évoluer au sein de l'application ?

Cette présentation proposera un retour d'expérience sur un parcours complet, allant de l'outillage de génération de données à l'écriture de scénarios applicatifs, sans négliger aucune étape pour maîtriser vos données.


## installation locale :

* Dépendances :
```bash
npm ci && npm start
```
* génération des soures
```bash
npm run build
```

* Consultation des slides : http://localhost:9577
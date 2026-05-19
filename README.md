# conf_fixtures

## Résumé

Le mensonge des fixtures : scénariser plutôt que générer

Nos fixtures mentent.
Elles créent des entités, pas des situations. Un workflow métier de 15 étapes joué en 1,3 seconde, dans une seule persistance, ne prouve rien : la réalité, c'est trois semaines.
Et quand le bug remonte, les données de test ne tiennent plus : dump de prod anonymisé périmé, README de seed artisanal, fixtures Doctrine empilées sprint après sprint.

C'est à partir de ce constat, sur une plateforme d'agrément Symfony multi-rôles dont le cycle de vie s'étale sur des semaines, qu'on a arrêté de **générer** des données pour commencer à **scénariser** des histoires.

Dans ce REX, je raconte le passage des fixtures jetables à des **scénarios YAML** versionnés, qui décrivent qui fait quoi et quand. **SymfonyClock** fige le temps à chaque action, les **services métier** rejouent le scénario pour de vrai, et l'application elle-même valide ses propres données.

Bonus : les scénarios deviennent une documentation vivante, partagée avec la QA et le PO.

À la sortie, vous saurez monter le même système chez vous, brique par brique — et pourquoi vos fixtures ne devraient plus jamais mentir.

## Ce que vous emporterez

- **Une fixture, ce n'est pas une ligne en base : c'est une situation métier.**
- **Pas de mécanisme de fixtures aujourd'hui ne ferme aucune porte demain : on peut scénariser sur un projet existant, sans tout réécrire.**
- **Une fois les scénarios en place, votre QA, vos PO et vos devs vous diront merci.**

## Plan du talk (≈ 45 min)

1. **Le constat** (≈ 5 min) — Comment on gère les données de test aujourd'hui : dumps de prod, README de seed artisanaux, fixtures Doctrine empilées. Pourquoi on s'en accommode.
2. **Le projet** (≈ 5 min) — Plateforme d'agrément Symfony multi-rôles, workflow long. Pourquoi les fixtures classiques ont craqué chez nous.
3. **Fixtures à l'ancienne** (≈ 8 min) — `Fixture`, `DependentFixtureInterface`, registre de références. Là où ça commence à coincer.
4. **Le temps comme citoyen de première classe** (≈ 7 min) — `SymfonyClock`, `MockClock`, et pourquoi 1,3 s ne suffit pas à instruire un dossier.
5. **Scénariser** (≈ 12 min) — Format YAML, parsing, exécution via services métier. On déroule un scénario, exemples à l'appui.
6. **Au-delà du test** (≈ 5 min) — Documentation vivante, scénarios partagés avec QA/PO, génération automatique.
7. **Pour démarrer chez vous** (≈ 3 min) — Par petites touches, sans tout réécrire.

## Bio

Lead Dev backend et architecte chez onepoint, je code au quotidien en Symfony, Drupal et API Platform. Mon parcours est un peu atypique : dix ans de chefferie de projet, puis un virage technique il y a sept ans pour replonger dans le code — et ne plus en ressortir. Persuadé que le café est le seul langage compatible avec tous les frameworks et que la pause café reste la meilleure session de pair programming, je pratique l'aïkido et je reste à l'affût de la prochaine série à binge-watcher ou du film à voir.

## Bio (EN)

Lead Backend Developer and Architect at onepoint, I code daily in Symfony, Drupal and API Platform. My background is a bit unusual: ten years in project management, then a technical pivot seven years ago to dive back into code — and never come up for air. Convinced that coffee is the only language compatible with every framework and that the coffee break remains the best pair programming session, I practice aikido and am always on the lookout for the next series to binge-watch or movie to catch.

## installation locale :

* Dépendances :
```bash
npm ci && npm start
```
* génération des sources
```bash
npm run build
```

* Consultation des slides : http://localhost:9577
# TWCxHomeAssistant

# Cahier des charges

## Introduction

Le but de ce projet est de rejoindre 2 système indépendant afin de les faire fonctionner ensemble.
Le premier système est une installation solar edge (Compteur photo voltaique) qui mesure l'energie produite/exporté instantné.
Le 2 ème système est une borne de charge tesla controllé par un raspberry par un bus RS-485.
La configuration de base du raspberry est un frontend web qui permet de régler la charge de manière statique.
On peut choisire le courant délivré a la brone de 6 amper a 16 amper en 240V 3 phase ce qui nous donne un controle de puissance d'env 4kw - 12kw.

## Objectifs du projet

 - Refaire le frontend du projet de départ
    - https://github.com/dracoventions/TWCManager
 - Comprendre commend les commands sont envoyé au TWC
 - Implémenter 3 mode
    - Mode eco -> Suit la production solaire
    - Mode Night -> Met la TWC a 16a pour le tariff de nuit
    - Mode Boost -> Met la TWC a 16a jusqua changment en Mode Night
- Pour le mode ECO
    - Chercher la prodcution sur la compteur solar edge
    - Transformer le delta de production en courant pour la TWC
    - Appliquer le nouveau courrant
- Pour le mode Night
    - Ce met en mode night automatiquement quand le tarriff de nuit commance et attend le tarrif de journée le matin pour ce mettre en eco
- Pour le mode boost
    - Met a 16a et attand le mode night
- Installer le tout sur un raspberry
    - Ressistance au coupure de courant
    - Ressistance au coupure de Réssau
    - Logging de data
    
#!/usr/bin/env bash
php app/console doctrine:generate:entity --entity=AppBundle:Book --format=annotation --fields="title:string(length=255 nullable=false) description:text(length=2047 nullable=true)" --no-interaction
php app/console doctrine:generate:entity --entity=AppBundle:Movie --format=annotation --fields="title:string(length=255 nullable=false) description:text(length=2047 nullable=true) quality:smallint(nullable=false)" --no-interaction
php app/console doctrine:generate:entity --entity=AppBundle:Tag --format=annotation --fields="name:string(length=255 nullable=false unique=true)" --no-interaction
php app/console doctrine:generate:entity --entity=AppBundle:Author --format=annotation --fields="name:string(length=255 nullable=false unique=true)" --no-interaction
php app/console doctrine:generate:entity --entity=AppBundle:Actor --format=annotation --fields="name:string(length=255 nullable=false unique=true)" --no-interaction

php app/console doctrine:generate:crud --entity=AppBundle:Book --route-prefix=book --with-write --no-interaction
#php app/console doctrine:generate:crud --entity=AppBundle:Movie --route-prefix=movie --with-write --no-interaction
#php app/console doctrine:generate:crud --entity=AppBundle:Tag --route-prefix=tag --with-write --no-interaction
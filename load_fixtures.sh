#!/usr/bin/env bash
php app/console doctrine:schema:drop -f
php app/console doctrine:schema:update -f
php app/console doctrine:fixtures:load --no-interaction
php app/console hautelook_alice:doctrine:fixtures:load --append --no-interaction
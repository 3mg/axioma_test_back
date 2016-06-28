#!/usr/bin/env bash
php app/console doctrine:schema:drop -f -e=dev
php app/console doctrine:schema:update -f -e=dev
php app/console doctrine:fixtures:load --no-interaction -e=dev
php app/console hautelook_alice:doctrine:fixtures:load --append --no-interaction -e=dev
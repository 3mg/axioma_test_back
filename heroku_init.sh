#!/usr/bin/env bash

hhvm `which composer` update
git add .
fit commit -a -m"~"

heroku create books-movies
heroku config:set SYMFONY_ENV=prod

git push heroku master
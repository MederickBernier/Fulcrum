.PHONY: up down build shell logs fresh

up:
	docker compose up -d

down:
	docker compose down

build: 
	docker compose build --no-cache

shell:
	docker compose exec php bash

logs:
	docker compose logs -f

logs-php:
	docker compose logs -f php

logs-nginx:
	docker compose logs -f nginx

fresh:
	docker compose down -v
	docker compose build --no-cache
	docker compose up -d
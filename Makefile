# Запуск приложения для разработки
app-start-dev: rm-vendor app-build-dev app-up-dev load-vendor-dev
app-start-prod: app-build-prod app-up-prod

app-build-dev: # сборка проекта с указанием имени пользователя
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml build --build-arg user=$(shell whoami) --build-arg uid=$(shell id -u)
app-build-no-cache-dev: # сборка без кэша
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml build --build-arg user=$(shell whoami) --build-arg uid=$(shell id -u) --no-cache
app-up-dev: # запуск проекта
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml up -d
app-stop-dev: # остановка проекта
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml stop
app-down-dev: # удаление контейнеров проекта
	docker-compose -f docker-compose.yml -f docker-compose.dev.yml down

app-build-prod: # сборка проекта для прода
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --build-arg user=$(shell whoami) --build-arg uid=$(shell id -u)
app-build-no-cache-prod: # сборка без кэша
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml build --build-arg user=$(shell whoami) --build-arg uid=$(shell id -u) --no-cache
app-up-prod: # запуск проекта
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d
app-stop-prod: # остановка проекта
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml stop
app-down-prod: # удаление контейнеров проекта
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml down

php-reindex-books:
    docker-compose exec whattoread-php-fpm php artisan reindex:books

### Команды для работы с контейнерами приложения (прод)
exec-php-fpm: # заходим в контейнер с php
	docker-compose exec whattoread-php-fpm bash
exec-nginx: # заходим в контейнер с nginx
	docker-compose exec whattoread-nginx bash

### Копирование зависимостей локально из контейнера
rm-vendor:
	rm -rf ./vendor/*
load-vendor:
	rm -rf ./vendor/*
	docker cp whattoread-php-fpm:/opt/www/vendor/. ./vendor
load-vendor-dev:
	rm -rf ./vendor/*
	docker cp whattoread-php-fpm-dev:/opt/www/vendor/. ./vendor

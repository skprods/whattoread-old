# Запуск приложения для разработки
app-start-dev: app-build-dev app-up-dev

app-build-dev: # сборка проекта с указанием имени пользователя
	docker-compose -f docker-compose.yml build --build-arg user=$(shell whoami) --build-arg uid=$(shell id -u)
app-build-no-cache-dev: # сборка без кэша
	docker-compose -f docker-compose.yml build --build-arg user=$(shell whoami) --build-arg uid=$(shell id -u) --no-cache
app-up-dev: # запуск проекта
	docker-compose -f docker-compose.yml up -d
app-stop-dev: # остановка проекта
	docker-compose -f docker-compose.yml stop
app-down-dev: # удаление контейнеров проекта
	docker-compose -f docker-compose.yml down

### Команды для работы с контейнерами приложения
exec-php-fpm: # заходим в контейнер с php
	docker-compose exec whattoread-php-fpm bash
exec-nginx: # заходим в контейнер с nginx
	docker-compose exec whattoread-nginx bash

### Копирование зависимостей локально из контейнера
load-vendor:
	rm -rf ./vendor/*
	docker cp whattoread-php-fpm:/opt/www/vendor/. ./vendor

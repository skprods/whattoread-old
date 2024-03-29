# Запуск приложения для разработки
app-start-dev: rm-vendor app-build-dev app-up-dev load-vendor-dev app-supervisor-start redis-config
app-start-prod: app-build-prod app-up-prod app-supervisor-start redis-config

app-build-dev: # сборка проекта с указанием имени пользователя
	docker compose -f docker-compose.yml -f docker-compose.dev.yml build
app-build-no-cache-dev: # сборка без кэша
	docker compose -f docker-compose.yml -f docker-compose.dev.yml build --no-cache
app-up-dev: # запуск проекта
	docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d
app-stop-dev: # остановка проекта
	docker compose -f docker-compose.yml -f docker-compose.dev.yml stop
app-down-dev: # удаление контейнеров проекта
	docker compose -f docker-compose.yml -f docker-compose.dev.yml down

app-build-prod: # сборка проекта для прода
	docker compose -f docker-compose.yml -f docker-compose.prod.yml build
app-build-no-cache-prod: # сборка без кэша
	docker compose -f docker-compose.yml -f docker-compose.prod.yml build --no-cache
app-up-prod: # запуск проекта
	docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
app-stop-prod: # остановка проекта
	docker compose -f docker-compose.yml -f docker-compose.prod.yml stop
app-down-prod: # удаление контейнеров проекта
	docker compose -f docker-compose.yml -f docker-compose.prod.yml down

php-reindex-books:
    docker-compose exec whattoread-php-fpm php artisan reindex:books

### Команды для работы с контейнерами приложения
exec-php-fpm: # заходим в контейнер с php
	docker compose exec whattoread-php-fpm bash
exec-nginx: # заходим в контейнер с nginx
	docker compose exec whattoread-nginx bash
exec-redis: # заходим в контейнер с redis
	docker compose exec whattoread-redis bash
exec-percona: # заходим в контейнер с redis
	docker compose exec whattoread-percona bash

app-supervisor-start: # запуск supervisor для очередей
	docker compose exec -u root whattoread-php-fpm /usr/sbin/service supervisor start
app-supervisor-stop: # остановка supervisor
	docker compose exec -u root whattoread-php-fpm /usr/sbin/service supervisor stop
app-supervisor-restart: # рестарт supervisor
	docker compose exec -u root whattoread-php-fpm /usr/sbin/service supervisor restart
app-supervisor-status: # статус supervisor
	docker compose exec -u root whattoread-php-fpm /usr/sbin/service supervisor status

### Копирование зависимостей локально из контейнера
rm-vendor:
	rm -rf ./vendor
load-vendor:
	rm -rf ./vendor
	docker cp whattoread-php-fpm:/var/www/vendor/. ./vendor
load-vendor-dev:
	rm -rf ./vendor
	docker cp whattoread-php-fpm-dev:/var/www/vendor/. ./vendor

redis-config:
	docker compose exec whattoread-redis redis-cli config set stop-writes-on-bgsave-error no
	docker compose exec whattoread-redis redis-cli config get stop-writes-on-bgsave-error

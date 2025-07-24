.PHONY: help start stop setup shell console logs clean migration migrate

help: ## Zeigt verfügbare Befehle
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

start: ## Startet Docker Container
	docker-compose up -d

stop: ## Stoppt Docker Container
	docker-compose down

setup: ## Führt das Setup-Skript aus (nach erstem Start)
	docker-compose exec app /var/www/html/docker/setup-entities.sh

shell: ## Öffnet Shell im App-Container
	docker-compose exec app sh

console: ## Führt Symfony Console-Befehle aus (z.B. make console CMD="cache:clear")
	docker-compose exec app php bin/console $(CMD)

logs: ## Zeigt Container-Logs
	docker-compose logs -f

clean: ## Stoppt Container und entfernt Volumes
	docker-compose down -v

migration: ## Erstellt neue Migration
	docker-compose exec app php bin/console make:migration

migrate: ## Führt Migrationen aus
	docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Beispiel-Befehle:
# make start          - Container starten
# make setup          - Entitäten erstellen (nach erstem Start)
# make console CMD="cache:clear" - Cache leeren
# make migration      - Neue Migration erstellen
# make migrate        - Migrationen ausführen

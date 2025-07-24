.PHONY: help start stop install setup shell console logs clean migration migrate

help: ## Zeigt verfügbare Befehle
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

start: ## Startet Docker Container
	docker-compose up -d

stop: ## Stoppt Docker Container
	docker-compose down

install: ## Baut Container, installiert Abhängigkeiten und führt Setup aus
	docker-compose up -d --build
	docker-compose exec app composer install --no-interaction
	@echo "Prüfe ob bin/console existiert..."
	docker compose exec --workdir /var/www/html app test -f bin/console && echo "bin/console gefunden" || echo "bin/console nicht gefunden"
	@echo "Räume Cache manuell auf..."
	docker compose exec --workdir /var/www/html app rm -rf var/cache/* || true
	@echo "Installation abgeschlossen!"
	docker compose exec --workdir /var/www/html app /usr/local/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes
	@echo "CS Fixer abgeschlossen!"

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

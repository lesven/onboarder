.PHONY: help start stop install setup shell console logs clean migration migrate phpstan

# Docker Compose Command (kann für CI überschrieben werden)
DOCKER_COMPOSE := docker compose

help: ## Zeigt verfügbare Befehle
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-15s\033[0m %s\n", $$1, $$2}'

start: ## Startet Docker Container
	$(DOCKER_COMPOSE) up -d

stop: ## Stoppt Docker Container
	$(DOCKER_COMPOSE) down
deploy:
	git pull
	make install
	git reset --hard HEAD

install: ## Baut Container, installiert Abhängigkeiten und führt Setup aus
	$(DOCKER_COMPOSE) up -d --build
	$(DOCKER_COMPOSE) exec app composer install --no-interaction
	@echo "Prüfe ob bin/console existiert..."
	$(DOCKER_COMPOSE) exec --workdir /var/www/html app test -f bin/console && echo "bin/console gefunden" || echo "bin/console nicht gefunden"
	@echo "Räume Cache manuell auf..."
	$(DOCKER_COMPOSE) exec --workdir /var/www/html app rm -rf var/cache/* || true
	@echo "Installation abgeschlossen!"
	make cache
	make setup-direct

cache: ## Leert den Symfony Cache
	$(DOCKER_COMPOSE) exec app php bin/console cache:clear
fix:
	$(DOCKER_COMPOSE) exec app /usr/local/bin/php-cs-fixer fix --diff --allow-risky=yes
	@echo "CS Fixer abgeschlossen!"
setup: ## Führt das Setup-Skript aus (nach erstem Start)
	$(DOCKER_COMPOSE) exec app chmod +x /var/www/html/docker/setup-entities.sh
	$(DOCKER_COMPOSE) exec app /var/www/html/docker/setup-entities.sh

setup-direct: ## Führt Setup-Befehle direkt aus (Fallback wenn setup-script nicht funktioniert)
	$(DOCKER_COMPOSE) exec app php bin/console doctrine:migrations:migrate --no-interaction
	$(DOCKER_COMPOSE) exec app php bin/console cache:clear
	$(DOCKER_COMPOSE) exec app php bin/console doctrine:schema:validate
	@echo "Setup-Befehle direkt ausgeführt!"

shell: ## Öffnet Shell im App-Container
	$(DOCKER_COMPOSE) exec app sh

console: ## Führt Symfony Console-Befehle aus (z.B. make console CMD="cache:clear")
	$(DOCKER_COMPOSE) exec app php bin/console $(CMD)

logs: ## Zeigt Container-Logs
	$(DOCKER_COMPOSE) logs -f

clean: ## Stoppt Container und entfernt Volumes
	$(DOCKER_COMPOSE) down -v

migration: ## Erstellt neue Migration
	$(DOCKER_COMPOSE) exec app php bin/console make:migration

migrate: ## Führt Migrationen aus
	$(DOCKER_COMPOSE) exec app php bin/console doctrine:migrations:migrate --no-interaction

check: ## Führt Code-Quality-Checks aus
	$(DOCKER_COMPOSE) exec app php-cs-fixer fix --dry-run --diff
	$(DOCKER_COMPOSE) exec app php bin/console lint:twig templates/
	$(DOCKER_COMPOSE) exec app php bin/console lint:yaml config/
	$(DOCKER_COMPOSE) exec app vendor/bin/phpstan analyse --no-progress --memory-limit=1G
	$(DOCKER_COMPOSE) exec app vendor/bin/phpmd src text phpmd.xml

phpstan: ## Führt statische Analyse mit PHPStan aus
	$(DOCKER_COMPOSE) exec app vendor/bin/phpstan analyse --no-progress --memory-limit=1G

phpmd: ## Führt PHP Mess Detector aus
	$(DOCKER_COMPOSE) exec app vendor/bin/phpmd src text phpmd.xml

check-all: ## Führt alle Quality-Checks und Tests aus
	make check
	make test

test: ## Führt PHPUnit Tests aus
	$(DOCKER_COMPOSE) exec app php bin/phpunit

test-verbose: ## Führt PHPUnit Tests mit detaillierter Ausgabe aus
	$(DOCKER_COMPOSE) exec app php bin/phpunit --verbose

test-coverage: ## Führt PHPUnit Tests mit Code Coverage aus
	$(DOCKER_COMPOSE) exec app php bin/phpunit --coverage-html var/coverage

test-filter: ## Führt spezifische Tests aus (make test-filter FILTER=EncryptSmtpPasswordsCommandTest)
	$(DOCKER_COMPOSE) exec app php bin/phpunit --filter $(FILTER)

test-watch: ## Führt Tests automatisch bei Dateiänderungen aus (benötigt fswatch)
	@echo "Watching for file changes... Press Ctrl+C to stop"
	@fswatch -o src/ tests/ | xargs -n1 -I{} make test || echo "fswatch not found. Install with: brew install fswatch"

ci-install: ## Installation für CI/CD (ohne interaktive Eingaben)
	$(DOCKER_COMPOSE) up -d --build --quiet-pull
	$(DOCKER_COMPOSE) exec -T app composer install --no-interaction --no-dev --optimize-autoloader
	$(DOCKER_COMPOSE) exec -T app php bin/console cache:clear --env=prod --no-interaction

# Beispiel-Befehle:
# make start          - Container starten
# make setup          - Entitäten erstellen (nach erstem Start)
# make console CMD="cache:clear" - Cache leeren
# make migration      - Neue Migration erstellen
# make migrate        - Migrationen ausführen

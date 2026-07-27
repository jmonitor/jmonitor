# Dev environment (Docker) — see compose.yaml and README "Development setup".
#
# JMONITOR_HTTPS=1 adds the HTTPS overlay (see CONTRIBUTING.md "HTTPS in dev").
# `export JMONITOR_HTTPS=1` in your shell profile makes it permanent. The files are
# passed explicitly because -f disables compose's automatic pickup of the
# (gitignored, personal) compose.override.yaml — it stays last, so it keeps winning.
COMPOSE_FILES = -f compose.yaml
ifeq ($(JMONITOR_HTTPS),1)
COMPOSE_FILES += -f compose.https.yaml
endif
ifneq ($(wildcard compose.override.yaml),)
COMPOSE_FILES += -f compose.override.yaml
endif

DC = docker compose $(COMPOSE_FILES)

ifeq ($(JMONITOR_HTTPS),1)
DASH_URL = https://dash.jmonitor.localhost:$(or $(JMONITOR_DEV_HTTPS_PORT),8443)
else
DASH_URL = http://dash.jmonitor.localhost
endif

# Pretty output — ANSI colors, auto-disabled when NO_COLOR is set (https://no-color.org).
# Values hold raw escapes; they are always emitted via printf, which interprets them.
ifndef NO_COLOR
C_OFF  := \033[0m
C_BOLD := \033[1m
C_DIM  := \033[2m
C_STEP := \033[1;36m
C_OK   := \033[1;32m
endif

.PHONY: up down setup provision demo monitor sh test phpstan cs cs-fix perms ca

up:
	$(DC) up -d --wait

down:
	$(DC) --profile "*" down

## Full cold start: containers + dependencies + database schema.
setup:
	@printf "\n$(C_STEP)▶ [1/4] Starting containers$(C_OFF)\n"
	@$(DC) up -d --wait
	@printf "\n$(C_STEP)▶ [2/4] Installing PHP dependencies$(C_OFF)\n"
	@$(DC) exec app composer install
	@printf "\n$(C_STEP)▶ [3/4] Applying database migrations$(C_OFF)\n"
	@$(DC) exec app php bin/console doctrine:migrations:migrate --no-interaction
	@printf "\n$(C_STEP)▶ [4/4] Provisioning dev data$(C_OFF)\n"
	@$(DC) exec app php bin/console app:dev:provision
	@printf "\n$(C_OK)══════════════════════════════════════════════$(C_OFF)\n"
	@printf "$(C_OK)  ✔  Environment ready$(C_OFF)\n"
	@printf "$(C_OK)══════════════════════════════════════════════$(C_OFF)\n\n"
	@printf "  $(C_BOLD)Dashboard$(C_OFF)   $(DASH_URL)\n"
	@printf "  $(C_BOLD)Login$(C_OFF)       dev@jmonitor.io / dev\n\n"
	@printf "  $(C_BOLD)Next steps$(C_OFF)\n"
	@printf "    $(C_DIM)make monitor$(C_OFF)     runs jmonitor:collect -vv in the app container (real self-monitoring metrics, live output)\n"
	@printf "    $(C_DIM)make demo$(C_OFF)        creates the demo project + a dedicated VIEWER user, fed by a synthetic-metrics worker\n"
	@printf "    $(C_DIM)make provision$(C_OFF)   re-link your dev user to the demo project — run after $(C_BOLD)make demo$(C_OFF)\n\n"

## Re-run dev provisioning. Idempotent; also links the dev user to the demo project once `make demo` has created it.
provision:
	@printf "\n$(C_STEP)▶ Provisioning dev data$(C_OFF)\n"
	@$(DC) exec app php bin/console app:dev:provision

## Synthetic-metrics worker feeding the demo account.
demo:
	$(DC) --profile demo up -d

## Real self-monitoring metrics into the dev project (foreground, Ctrl+C to stop). Needs `make setup` first.
# -vv surfaces the collector's info/notice logs (pushes, retries, limits) via the dev `console` monolog handler.
monitor:
	$(DC) exec app php bin/console jmonitor:collect -vv

## Export Caddy's internal root CA (HTTPS mode) so the browser trusts the dev certificates.
ca:
	@$(DC) exec -T app cat /data/caddy/pki/authorities/local/root.crt > caddy-root-ca.crt
	@printf "\n$(C_OK)  ✔  caddy-root-ca.crt exported$(C_OFF)\n\n"
	@printf "  Trust it once — Windows (browser side of WSL2), no admin needed:\n"
	@printf "    $(C_DIM)certutil.exe -addstore -user Root caddy-root-ca.crt$(C_OFF)\n"
	@printf "  Linux:\n"
	@printf "    $(C_DIM)sudo cp caddy-root-ca.crt /usr/local/share/ca-certificates/ && sudo update-ca-certificates$(C_OFF)\n"
	@printf "  Firefox keeps its own store: Settings → Certificates → Import.\n\n"
	@printf "  $(C_DIM)Restart the browser afterwards, then open $(DASH_URL)$(C_OFF)\n\n"

sh:
	$(DC) exec app bash

## Fix root-owned files created by the containers (the server runs as root on the bind mount).
perms:
	$(DC) exec -T app chown -Rh $(shell id -u):$(shell id -g) /app

test:
	$(DC) exec app composer run phpunit

phpstan:
	$(DC) exec app composer run phpstan

cs:
	$(DC) exec app composer run lint:check

cs-fix:
	$(DC) exec app composer run lint:fix

# Développement local — lier les packages locaux via path repositories Composer

link-bundle:
	composer config repositories.jmonitor-bundle '{"type":"path","url":"../jmonitor-bundle","options":{"symlink":true}}'
	composer require jmonitor/jmonitor-bundle:"*@dev" --no-scripts
	composer update jmonitor/jmonitor-bundle --no-scripts

link-collector:
	composer config repositories.collector '{"type":"path","url":"../collector","options":{"symlink":true}}'
	composer update jmonitor/collector --no-scripts

link: link-bundle link-collector

unlink:
	git checkout -- composer.json composer.lock
	composer install

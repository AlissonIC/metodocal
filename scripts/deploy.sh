#!/usr/bin/env bash
#
# TrevoNetwork — script de deploy.
#
# Pode ser rodado:
#   - via SSH:  bash scripts/deploy.sh
#   - via PHP:  /git no painel admin chama este arquivo
#
# Cada passo imprime um separador claro e SEGUE em frente mesmo se falhar
# (não usa `set -e`), porque a UI mostra cada bloco de saída e o admin decide.
#
# Idempotente: pode rodar quantas vezes quiser.

# Diretório raiz da aplicação (resolve o path real do diretório deste script).
APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR" || { echo "ERRO: não consegui entrar em $APP_DIR"; exit 1; }

# Cores ANSI (degradam pra texto puro se o terminal/pre não suportar)
G="\033[1;32m"; R="\033[1;31m"; Y="\033[1;33m"; B="\033[1;34m"; N="\033[0m"

step() {
  echo
  printf "${B}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${N}\n"
  printf "${B}▶ %s${N}\n" "$1"
  printf "${B}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${N}\n"
}

ok()   { printf "${G}✓ %s${N}\n" "$1"; }
fail() { printf "${R}✗ %s${N}\n" "$1"; }

run() {
  local label="$1"; shift
  step "$label"
  printf "${Y}\$ %s${N}\n" "$*"
  "$@"
  local rc=$?
  if [ $rc -eq 0 ]; then
    ok "$label OK"
  else
    fail "$label falhou (exit $rc)"
  fi
  return $rc
}

echo "=================================================="
echo "  Deploy TrevoNetwork — $(date '+%Y-%m-%d %H:%M:%S')"
echo "  Diretório: $APP_DIR"
echo "  Usuário:   $(whoami)"
echo "=================================================="

# 1) git pull (safe.directory inline pra hosts onde o dono difere do user PHP-FPM)
run "git pull"            git -c "safe.directory=*" pull --ff-only

# 2) composer install (sem --no-dev pra evitar tentativa de remover dev deps
#    quando o user não tem perm de delete em vendor/)
run "composer install"    composer install --no-interaction --optimize-autoloader

# 3) migrations
run "php artisan migrate" php artisan migrate --force

# 4) front-end (yarn — Vite).
#  --ignore-engines: tolera mismatch de versão do Node em hosts gerenciados
#  --network-timeout: evita drop em redes lentas
#  SEM --frozen-lockfile: deixa yarn atualizar yarn.lock quando package.json
#  diverge (acontece quando dep é adicionada local mas o lock não foi commitado).
run "yarn install"        yarn install --ignore-engines --network-timeout 120000
run "yarn build"          yarn build

# 5) PREPARAR storage/ ANTES de qualquer artisan.
#  Ordem importa: se rodarmos `php artisan cache:clear` com perms ruins, o próprio
#  bootstrap do Laravel pode escrever em storage/framework e cair no tempnam(/tmp).
#  Então a sequência é: garantir dirs → chown → chmod → DEPOIS rodar artisan.

# 5.1) garantir estrutura completa de storage/ e bootstrap/cache.
run "ensure storage dirs" mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/framework/testing \
  storage/logs \
  storage/app/public \
  storage/app/private \
  bootstrap/cache

# 5.2) corrigir dono/permissão.
#  - chown só funciona se o script rodar como root (ou com sudoers NOPASSWD).
#    Se o user atual já for o dono, é no-op silencioso. Erro tolerado (sem `set -e`).
#  - 775 garante escrita pro grupo (PHP-FPM e SSH podem ser users diferentes
#    no mesmo grupo).
APP_USER="$(stat -c '%U' "$APP_DIR" 2>/dev/null || echo "")"
APP_GROUP="$(stat -c '%G' "$APP_DIR" 2>/dev/null || echo "")"
if [ -n "$APP_USER" ] && [ -n "$APP_GROUP" ]; then
  run "chown storage/bootstrap" chown -R "$APP_USER:$APP_GROUP" storage bootstrap/cache
fi
run "chmod storage/bootstrap" chmod -R 775 storage bootstrap/cache

# 5.3) AGORA sim limpar caches antigos (já com perms certas, sem warnings).
run "cache:clear"  php artisan cache:clear
run "config:clear" php artisan config:clear
run "route:clear"  php artisan route:clear
run "view:clear"   php artisan view:clear

# 5.4) reconstruir caches.
#  Cria os arquivos UMA vez, já com o dono certo, e o request só lê.
run "config:cache" php artisan config:cache
run "route:cache"  php artisan route:cache
run "view:cache"   php artisan view:cache

# 5.5) reaplica chown/chmod nos arquivos que os artisans acima criaram.
#  cache:cache/route:cache/view:cache cria PHP files novos. Se o user que rodou
#  o deploy é diferente do user do PHP-FPM, esses arquivos nascem com dono errado.
if [ -n "$APP_USER" ] && [ -n "$APP_GROUP" ]; then
  run "re-chown após cache" chown -R "$APP_USER:$APP_GROUP" storage bootstrap/cache
fi
run "re-chmod após cache" chmod -R 775 storage bootstrap/cache

# 6) reiniciar workers do supervisor pra que peguem o código novo.
#  - `queue:restart` é o caminho canônico do Laravel: grava um sinal que
#    cada worker lê no topo do loop e encerra graciosamente. O supervisor
#    (autorestart=true) sobe instâncias novas com o código atualizado.
#    Precisa rodar DEPOIS de cache:clear, senão o sinal é apagado.
#  - Se `supervisorctl` estiver disponível e o usuário tiver permissão
#    (root, ou sudoers NOPASSWD pra este comando), faz um restart "duro"
#    como reforço — útil quando o worker travou e não está iterando.
run "queue:restart" php artisan queue:restart

step "supervisorctl restart workers"
if command -v supervisorctl >/dev/null 2>&1; then
  # Descobre dinamicamente os grupos/programas cujo nome bate com worker|queue
  # (o nome varia por host: laravel-worker, trevonetwork-worker, queue-worker, …).
  # Lista no formato "grupo:programa" → extraímos só os nomes únicos de grupo.
  GROUPS=$(supervisorctl status 2>/dev/null \
    | awk '{print $1}' \
    | grep -Ei '(worker|queue)' \
    | awk -F: '{print $1}' \
    | sort -u)

  if [ -z "$GROUPS" ]; then
    printf "${Y}Nenhum programa de worker encontrado no supervisorctl status${N}\n"
    printf "${Y}(o queue:restart acima já avisou os workers existentes)${N}\n"
    ok "supervisorctl restart pulado"
  else
    any_ok=0
    any_fail=0
    for grp in $GROUPS; do
      printf "${Y}\$ supervisorctl restart ${grp}:*${N}\n"
      if supervisorctl restart "${grp}:*" 2>&1; then
        any_ok=1
      else
        any_fail=1
      fi
    done
    if [ $any_ok -eq 1 ] && [ $any_fail -eq 0 ]; then
      ok "supervisorctl restart OK"
    elif [ $any_ok -eq 1 ]; then
      ok "supervisorctl restart OK (alguns grupos falharam)"
    else
      fail "supervisorctl restart falhou (sem permissão? — queue:restart já foi suficiente)"
    fi
  fi
else
  printf "${Y}supervisorctl não encontrado — pulando restart duro (queue:restart cobriu)${N}\n"
fi

echo
echo "=================================================="
echo "  Deploy finalizado — $(date '+%Y-%m-%d %H:%M:%S')"
echo "=================================================="

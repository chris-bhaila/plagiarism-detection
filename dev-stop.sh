#!/usr/bin/env bash
# Kills leftovers from dev-start.sh (or from services you started by hand):
#   - php artisan serve and its child server
#   - php artisan queue:work / queue:listen
#   - uvicorn main:app and its reload worker
#   - anything still listening on ports 8000 / 8001
#
# Usage: ./dev-stop.sh [--dry-run] [--all]
#   --dry-run  list what would be killed, kill nothing
#   --all      match artisan/uvicorn processes from ANY project. By default only ones whose
#              working directory is this project / the similarity service are matched, so a
#              queue worker belonging to a different Laravel project is left alone.
#              (Whatever is listening on the two ports is always killed, per the port rule.)
#
# Overridable: LARAVEL_PORT (8000), SIMILARITY_PORT (8001), SIMILARITY_SERVICE_DIR.

set -u

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_DIR_INPUT="${SIMILARITY_SERVICE_DIR:-$PROJECT_DIR/../../similarity-service/similarity-service}"
SERVICE_DIR="$(cd "$SERVICE_DIR_INPUT" 2>/dev/null && pwd || echo "$SERVICE_DIR_INPUT")"
LARAVEL_PORT="${LARAVEL_PORT:-8000}"
SIMILARITY_PORT="${SIMILARITY_PORT:-8001}"

DRY_RUN=0
MATCH_ALL=0
for arg in "$@"; do
  case $arg in
    --dry-run) DRY_RUN=1 ;;
    --all) MATCH_ALL=1 ;;
    -h|--help) sed -n '2,15p' "$0"; exit 0 ;;
    *) echo "Unknown option: $arg (try --help)" >&2; exit 2 ;;
  esac
done

for tool in pgrep ss; do
  command -v "$tool" >/dev/null 2>&1 || { echo "✗ '$tool' is required but not found." >&2; exit 1; }
done

# Prints PIDs matching a pgrep -f pattern, optionally only those whose cwd is $2.
pids_matching() {
  local pattern=$1 want_cwd=$2 pid
  for pid in $(pgrep -f "$pattern" 2>/dev/null); do
    [ "$pid" = "$$" ] && continue
    if [ "$MATCH_ALL" = 1 ] || [ "$(readlink "/proc/$pid/cwd" 2>/dev/null)" = "$want_cwd" ]; then
      echo "$pid"
    fi
  done
}

port_pids() {
  local port
  for port in "$LARAVEL_PORT" "$SIMILARITY_PORT"; do
    ss -H -ltnp "sport = :$port" 2>/dev/null | grep -o 'pid=[0-9]*' | cut -d= -f2
  done
}

describe() { ps -o pid=,args= -p "$1" 2>/dev/null | cut -c1-150; }

alive() { kill -0 "$1" 2>/dev/null; }

kill_pids() { # kill_pids <pid>... : TERM, wait up to 5s, then KILL
  local pid t any
  [ "$#" -eq 0 ] && return
  for pid in "$@"; do
    echo "  killing $(describe "$pid")"
    kill -TERM "$pid" 2>/dev/null
  done
  for ((t = 0; t < 25; t++)); do
    any=0
    for pid in "$@"; do alive "$pid" && any=1; done
    [ "$any" = 0 ] && return
    sleep 0.2
  done
  for pid in "$@"; do
    if alive "$pid"; then
      echo "  ! $pid ignored SIGTERM — SIGKILL"
      kill -KILL "$pid" 2>/dev/null
    fi
  done
}

# Phase 1: the supervising parents. Kill these before their children — `artisan serve` will
# otherwise restart its server on the next port (8001!) when the child dies, and uvicorn's
# reloader respawns its worker.
mapfile -t PARENTS < <(
  {
    pids_matching 'artisan serve' "$PROJECT_DIR"
    pids_matching 'artisan queue:(work|listen)' "$PROJECT_DIR"
    pids_matching 'uvicorn main:app' "$SERVICE_DIR"
  } | sort -un
)

# Phase 2 candidates are computed after phase 1 (see below), but list them now for --dry-run.
mapfile -t LISTENERS < <(port_pids | sort -un)

if [ "${#PARENTS[@]}" -eq 0 ] && [ "${#LISTENERS[@]}" -eq 0 ]; then
  echo "✓ Nothing to stop — no dev processes found and ports $LARAVEL_PORT/$SIMILARITY_PORT are free."
  exit 0
fi

if [ "$DRY_RUN" = 1 ]; then
  echo "Would kill (parents first):"
  for pid in $(printf '%s\n' "${PARENTS[@]}" "${LISTENERS[@]}" | awk 'NF && !seen[$0]++'); do echo "  $(describe "$pid")"; done
  exit 0
fi

echo "Stopping supervisors (serve / queue / uvicorn)..."
kill_pids "${PARENTS[@]}"

sleep 0.5
mapfile -t LEFTOVER < <(port_pids | sort -un)
if [ "${#LEFTOVER[@]}" -gt 0 ]; then
  echo "Stopping remaining listeners on ports $LARAVEL_PORT/$SIMILARITY_PORT..."
  kill_pids "${LEFTOVER[@]}"
fi

echo
STATUS=0
for port in "$LARAVEL_PORT" "$SIMILARITY_PORT"; do
  if ss -H -ltn "sport = :$port" 2>/dev/null | grep -q .; then
    echo "✗ Port $port is STILL in use (possibly owned by another user — try sudo, or check: ss -ltnp)"
    STATUS=1
  else
    echo "✓ Port $port is free"
  fi
done
if pgrep -f 'artisan queue:(work|listen)' >/dev/null 2>&1 && [ "$MATCH_ALL" = 0 ]; then
  echo "! A queue worker from another directory is still running (left alone; use --all to kill it)."
fi
exit "$STATUS"

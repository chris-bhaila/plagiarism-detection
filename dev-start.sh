#!/usr/bin/env bash
# Starts everything needed to test the submission -> similarity-check flow:
#   1. Laravel dev server   (php artisan serve)
#   2. Queue worker         (php artisan queue:work)
#   3. Similarity service   (uvicorn main:app, from its own virtualenv)
# Ctrl+C stops all three. If anything is left behind, run ./dev-stop.sh.
#
# Overridable via environment:
#   SIMILARITY_SERVICE_DIR   path to the FastAPI service (default: see below)
#   LARAVEL_PORT             default 8000
#   SIMILARITY_PORT          default 8001 (Laravel's .env SIMILARITY_CHECK_API_URL must match)
#   SIMILARITY_START_TIMEOUT seconds to wait for the model to load (default 90)

set -u
set -o pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SERVICE_DIR_INPUT="${SIMILARITY_SERVICE_DIR:-$PROJECT_DIR/../../similarity-service/similarity-service}"
LARAVEL_PORT="${LARAVEL_PORT:-8000}"
SIMILARITY_PORT="${SIMILARITY_PORT:-8001}"
SIMILARITY_START_TIMEOUT="${SIMILARITY_START_TIMEOUT:-90}"
LOG_DIR="$PROJECT_DIR/storage/logs/dev"

LARAVEL_PID=""
QUEUE_PID=""
SIMILARITY_PID=""
PROBLEMS=0

ok()   { printf '✓ %s\n' "$*"; }
fail() { printf '✗ %s\n' "$*"; PROBLEMS=$((PROBLEMS + 1)); }
note() { printf '… %s\n' "$*"; }

# ---------------------------------------------------------------- preflight

for tool in php curl ss; do
  if ! command -v "$tool" >/dev/null 2>&1; then
    echo "✗ '$tool' is required but was not found in PATH." >&2
    exit 1
  fi
done

if ! SERVICE_DIR="$(cd "$SERVICE_DIR_INPUT" 2>/dev/null && pwd)"; then
  echo "✗ Similarity service directory not found: $SERVICE_DIR_INPUT" >&2
  echo "  Set SIMILARITY_SERVICE_DIR=/absolute/path/to/similarity-service and retry." >&2
  exit 1
fi

if [ ! -f "$SERVICE_DIR/main.py" ]; then
  echo "✗ $SERVICE_DIR/main.py not found — is that the right directory?" >&2
  exit 1
fi

if [ ! -f "$SERVICE_DIR/venv/bin/activate" ] || [ ! -x "$SERVICE_DIR/venv/bin/uvicorn" ]; then
  echo "✗ Virtualenv missing or incomplete: $SERVICE_DIR/venv (need bin/activate and bin/uvicorn)" >&2
  exit 1
fi

port_in_use() { ss -H -ltn "sport = :$1" 2>/dev/null | grep -q .; }

for port in "$LARAVEL_PORT" "$SIMILARITY_PORT"; do
  if port_in_use "$port"; then
    echo "✗ Port $port is already in use — something is already running." >&2
    echo "  Stop it first (./dev-stop.sh), then run this again." >&2
    exit 1
  fi
done

if pgrep -f 'artisan queue:(work|listen)' >/dev/null 2>&1; then
  echo "! A queue worker is already running. A second one is harmless but usually a mistake"
  echo "  (./dev-stop.sh clears it). Continuing anyway."
fi

if [ "$SIMILARITY_PORT" != "8001" ]; then
  echo "! SIMILARITY_PORT is $SIMILARITY_PORT but Laravel calls SIMILARITY_CHECK_API_URL from .env"
  echo "  (default http://127.0.0.1:8001). Similarity checks will not reach this instance."
fi

mkdir -p "$LOG_DIR"
: >"$LOG_DIR/laravel.log"
: >"$LOG_DIR/queue.log"
: >"$LOG_DIR/similarity.log"

# ---------------------------------------------------------------- cleanup

CLEANING=0

group_alive() { kill -0 -- "-$1" 2>/dev/null; }

cleanup() {
  [ "$CLEANING" = 1 ] && return
  CLEANING=1
  trap '' INT TERM HUP # a second Ctrl+C must not interrupt the shutdown

  local pids=() pid t alive
  for pid in "$LARAVEL_PID" "$QUEUE_PID" "$SIMILARITY_PID"; do
    [ -n "$pid" ] && pids+=("$pid")
  done
  [ "${#pids[@]}" -eq 0 ] && return

  echo
  echo "Shutting down..."

  # Each service was started as its own process group (set -m), so signalling
  # -PID reaches the whole tree: php's child server, uvicorn's reload worker, etc.
  for pid in "${pids[@]}"; do kill -TERM -- "-$pid" 2>/dev/null; done

  for ((t = 0; t < 25; t++)); do # up to ~5s for a graceful exit
    alive=0
    for pid in "${pids[@]}"; do group_alive "$pid" && alive=1; done
    [ "$alive" = 0 ] && break
    sleep 0.2
  done

  for pid in "${pids[@]}"; do
    if group_alive "$pid"; then
      echo "! Process group $pid ignored SIGTERM — sending SIGKILL"
      kill -KILL -- "-$pid" 2>/dev/null
    fi
  done

  echo "✓ Stopped."
}

trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
trap 'exit 129' HUP

# ---------------------------------------------------------------- start

# Job control: every "&" job below becomes its own process group with
# pgid == pid, which is what makes `kill -- -PID` in cleanup() work. It also
# means a terminal Ctrl+C reaches only this script, not the children, so the
# shutdown is always orchestrated by cleanup().
set -m

wait_ready() { # wait_ready <pid> <timeout-seconds> <check-function>
  local pid=$1 timeout=$2 check=$3 i
  for ((i = 0; i < timeout; i++)); do
    kill -0 "$pid" 2>/dev/null || return 2 # process died while starting
    "$check" && return 0
    sleep 1
  done
  return 1
}

laravel_up() { curl -fsS -m 2 -o /dev/null "http://127.0.0.1:$LARAVEL_PORT/up" 2>/dev/null; }

similarity_up() {
  local body re='"status"[[:space:]]*:[[:space:]]*"ok"'
  body="$(curl -fsS -m 2 "http://127.0.0.1:$SIMILARITY_PORT/health" 2>/dev/null)" || return 1
  [[ $body =~ $re ]]
}

show_log_tail() { echo "  --- last lines of $1 ---"; tail -n 15 "$1" | sed 's/^/  | /'; }

# 1. Laravel
(cd "$PROJECT_DIR" && exec php artisan serve --host=127.0.0.1 --port="$LARAVEL_PORT") \
  >"$LOG_DIR/laravel.log" 2>&1 </dev/null &
LARAVEL_PID=$!

if wait_ready "$LARAVEL_PID" 20 laravel_up; then
  ok "Laravel server running on :$LARAVEL_PORT (GET /up responded)"
else
  fail "Laravel server did not come up on :$LARAVEL_PORT"
  show_log_tail "$LOG_DIR/laravel.log"
fi

# 2. Queue worker. --sleep=1 so a new submission is picked up within ~1s (default is 3s).
(cd "$PROJECT_DIR" && exec php artisan queue:work --sleep=1) \
  >"$LOG_DIR/queue.log" 2>&1 </dev/null &
QUEUE_PID=$!

sleep 2
if kill -0 "$QUEUE_PID" 2>/dev/null; then
  ok "Queue worker started (pid $QUEUE_PID) — still alive after 2s"
else
  fail "Queue worker exited immediately"
  show_log_tail "$LOG_DIR/queue.log"
fi

# 3. Similarity service. `exec` makes the subshell *become* uvicorn so $! is uvicorn's real PID.
# The absolute path to the venv's uvicorn is used on purpose: if activation ever silently
# failed, plain `uvicorn` could resolve to another install (e.g. ~/.local/bin/uvicorn).
(
  cd "$SERVICE_DIR" || exit 1
  set +u # some virtualenv activate scripts reference unset variables
  # shellcheck disable=SC1091
  source venv/bin/activate || exit 1
  exec "$SERVICE_DIR/venv/bin/uvicorn" main:app --reload --reload-exclude 'venv/*' \
    --host 127.0.0.1 --port "$SIMILARITY_PORT"
) >"$LOG_DIR/similarity.log" 2>&1 </dev/null &
SIMILARITY_PID=$!

note "Similarity service starting — it loads a sentence-transformer model, waiting up to ${SIMILARITY_START_TIMEOUT}s for /health..."
wait_ready "$SIMILARITY_PID" "$SIMILARITY_START_TIMEOUT" similarity_up
case $? in
  0) ok "Similarity service running on :$SIMILARITY_PORT (GET /health -> status ok)" ;;
  2) fail "Similarity service process exited during startup"
     show_log_tail "$LOG_DIR/similarity.log" ;;
  *) fail "Similarity service did not report healthy within ${SIMILARITY_START_TIMEOUT}s (process is still running)"
     show_log_tail "$LOG_DIR/similarity.log" ;;
esac

echo
if [ "$PROBLEMS" -eq 0 ]; then
  echo "All three services are up. App: http://127.0.0.1:$LARAVEL_PORT"
else
  echo "$PROBLEMS problem(s) above. The other services are still running."
fi
echo "Logs: tail -f $LOG_DIR/{laravel,queue,similarity}.log"
echo "Note: queue:work does not reload code — restart this script after editing a Job class."
echo "Press Ctrl+C to stop everything."

# ---------------------------------------------------------------- supervise

LARAVEL_DEAD=0
QUEUE_DEAD=0
SIMILARITY_DEAD=0

while true; do
  sleep 2 &
  wait $! # returns immediately when a trapped signal arrives, unlike a foreground sleep

  if [ "$LARAVEL_DEAD" = 0 ] && ! kill -0 "$LARAVEL_PID" 2>/dev/null; then
    LARAVEL_DEAD=1
    fail "Laravel server exited unexpectedly — see $LOG_DIR/laravel.log"
  fi
  if [ "$QUEUE_DEAD" = 0 ] && ! kill -0 "$QUEUE_PID" 2>/dev/null; then
    QUEUE_DEAD=1
    fail "Queue worker exited unexpectedly — submissions will NOT be checked. See $LOG_DIR/queue.log"
  fi
  if [ "$SIMILARITY_DEAD" = 0 ] && ! kill -0 "$SIMILARITY_PID" 2>/dev/null; then
    SIMILARITY_DEAD=1
    fail "Similarity service exited unexpectedly — see $LOG_DIR/similarity.log"
  fi
done

#!/bin/bash

# Test script for Docker permission handling and healthcheck
# Builds base and production images, then tests various runtime configurations

set -e

BASE_IMAGE_NAME="databasement-php-test"
PROD_IMAGE_NAME="databasement-test"
CONTAINER_NAME="databasement-test-container"
PORT=2227

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Track temp directories for cleanup
TEMP_DIRS=()

cleanup() {
    echo -e "\n${YELLOW}Cleaning up...${NC}"
    docker stop "$CONTAINER_NAME" 2>/dev/null || true
    docker rm "$CONTAINER_NAME" 2>/dev/null || true
    # Clean up all temp directories
    for dir in "${TEMP_DIRS[@]}"; do
        if [ -d "$dir" ]; then
            rm -rf "$dir" 2>/dev/null || true
        fi
    done
}

trap cleanup EXIT

# Create a temp data directory with correct ownership
# Usage: create_data_dir <uid> <gid>
create_data_dir() {
    local uid=$1
    local gid=$2
    local dir
    dir=$(mktemp -d)
    TEMP_DIRS+=("$dir")
    # Use docker to chown since the UID might not exist on host
    docker run --rm -v "$dir:/data" "$BASE_IMAGE_NAME:latest" chown "$uid:$gid" /data
    echo "$dir"
}

pass() {
    echo -e "${GREEN}✓ PASS${NC}: $1"
}

fail() {
    echo -e "${RED}✗ FAIL${NC}: $1"
    echo -e "${YELLOW}Container logs:${NC}"
    docker logs "$CONTAINER_NAME" 2>&1 | tail -30
    exit 1
}

info() {
    echo -e "${BLUE}→${NC} $1"
}

wait_for_healthy() {
    local max_attempts=30
    local attempt=1

    info "Waiting for container to be healthy..."

    while [ $attempt -le $max_attempts ]; do
        if curl -sf "http://localhost:$PORT/health" > /dev/null 2>&1; then
            pass "Healthcheck passed (attempt $attempt)"
            return 0
        fi
        sleep 1
        attempt=$((attempt + 1))
    done

    fail "Healthcheck failed after $max_attempts attempts"
}

verify_frankenphp() {
    local max_attempts=15
    local attempt=1

    info "Waiting for FrankenPHP to be ready..."

    while [ $attempt -le $max_attempts ]; do
        local logs
        logs=$(docker logs "$CONTAINER_NAME" 2>&1)

        if echo "$logs" | grep -q "FrankenPHP started"; then
            pass "FrankenPHP started successfully"
            return 0
        fi
        sleep 1
        attempt=$((attempt + 1))
    done

    fail "FrankenPHP did not start (missing 'FrankenPHP started' message)"
}

echo "=========================================="
echo "Docker Build & Permission Tests"
echo "=========================================="
echo ""

# Step 1: Build base image
echo -e "${YELLOW}Step 1: Building base image from docker/php/Dockerfile...${NC}"
docker build -f docker/php/Dockerfile -t "$BASE_IMAGE_NAME:latest" docker/php/ --quiet
pass "Base image built: $BASE_IMAGE_NAME:latest"
echo ""

# Step 2: Build production image
echo -e "${YELLOW}Step 2: Building production image from Dockerfile...${NC}"
docker build --build-arg BASE_IMAGE="$BASE_IMAGE_NAME:latest" -t "$PROD_IMAGE_NAME:latest" . --quiet
pass "Production image built: $PROD_IMAGE_NAME:latest"
echo ""

# Test 1: Default settings (root start, PUID=1000 PGID=1000)
echo "=========================================="
echo "Test 1: Default settings (PUID=1000 PGID=1000)"
echo "=========================================="
TEST_DATA_DIR=$(create_data_dir 1000 1000)
info "Created data dir: $TEST_DATA_DIR (owned by 1000:1000)"
info "Starting container with default settings..."

docker run -d \
    --name "$CONTAINER_NAME" \
    -p "$PORT:2226" \
    -v "$TEST_DATA_DIR:/data" \
    -e ENABLE_QUEUE_WORKER=true \
    -e DB_CONNECTION=sqlite \
    -e DB_DATABASE=/data/database.sqlite \
    "$PROD_IMAGE_NAME:latest"

sleep 3

LOGS=$(docker logs "$CONTAINER_NAME" 2>&1)
echo "$LOGS" | head -10

if echo "$LOGS" | grep -q "Setting up user application"; then
    pass "Permission setup ran"
else
    fail "Permission setup did not run"
fi

verify_frankenphp
wait_for_healthy

cleanup
echo ""

# Test 2: Custom PUID/PGID
echo "=========================================="
echo "Test 2: Custom PUID=1500 PGID=1500"
echo "=========================================="
TEST_DATA_DIR=$(create_data_dir 1500 1500)
info "Created data dir: $TEST_DATA_DIR (owned by 1500:1500)"
info "Starting container with PUID=1500 PGID=1500..."

docker run -d \
    --name "$CONTAINER_NAME" \
    -p "$PORT:2226" \
    -v "$TEST_DATA_DIR:/data" \
    -e PUID=1500 \
    -e PGID=1500 \
    -e ENABLE_QUEUE_WORKER=true \
    -e DB_CONNECTION=sqlite \
    -e DB_DATABASE=/data/database.sqlite \
    "$PROD_IMAGE_NAME:latest"

sleep 3

LOGS=$(docker logs "$CONTAINER_NAME" 2>&1)
echo "$LOGS" | head -10

if echo "$LOGS" | grep -q "Setting up user application with PUID=1500"; then
    pass "Custom PUID/PGID detected"
else
    fail "Custom PUID/PGID not detected"
fi

verify_frankenphp
wait_for_healthy

cleanup
echo ""

# Test 3: --user flag (no permission fix, runs as specified user)
echo "=========================================="
echo "Test 3: --user 1001:1001 flag"
echo "=========================================="
TEST_DATA_DIR=$(create_data_dir 1001 1001)
info "Created data dir: $TEST_DATA_DIR (owned by 1001:1001)"
info "Starting container with --user 1001:1001..."

docker run -d \
    --name "$CONTAINER_NAME" \
    -p "$PORT:2226" \
    --user 1001:1001 \
    -v "$TEST_DATA_DIR:/data" \
    -e ENABLE_QUEUE_WORKER=true \
    -e DB_CONNECTION=sqlite \
    -e DB_DATABASE=/data/database.sqlite \
    "$PROD_IMAGE_NAME:latest"

sleep 3

LOGS=$(docker logs "$CONTAINER_NAME" 2>&1)
echo "$LOGS" | head -10

if echo "$LOGS" | grep -q "Not running as root, skipping permission fix"; then
    pass "Permission fix skipped (expected when using --user)"
else
    fail "Should skip permission fix when using --user"
fi

if echo "$LOGS" | grep -q "supervisord started with pid"; then
    pass "Supervisor started successfully as non-root user"
else
    fail "Supervisor did not start"
fi

verify_frankenphp
wait_for_healthy

cleanup
echo ""

echo "=========================================="
echo -e "${GREEN}All tests passed!${NC}"
echo "=========================================="

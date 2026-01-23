#!/usr/bin/env bash
set -euo pipefail

cd "$CLAUDE_PROJECT_DIR"

echo "Repo: $(basename "$PWD")"
git status -sb

echo "Common tasks:"
echo "- Backend tests: composer run test"
echo "- Frontend lint: npm run lint"

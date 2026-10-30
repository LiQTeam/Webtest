#!/bin/bash
# Installs what the video skills in .claude/skills need to render in cloud sessions.
set -euo pipefail

if [ "${CLAUDE_CODE_REMOTE:-}" != "true" ]; then
  exit 0
fi

# Python Playwright for xilo-opus-video's render.py. The version matches the
# pre-installed Playwright browsers in /opt/pw-browsers, so no browser download.
if ! python3 -c "import playwright" 2>/dev/null; then
  python3 -m pip install --quiet --root-user-action=ignore "playwright==1.63.0" numpy
fi

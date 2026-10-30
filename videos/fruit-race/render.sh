#!/bin/bash
# Render one episode to out/miveh-star-<ep>.mp4: build timeline + audio, set loudness, render frames.
#   ./render.sh ep2
set -euo pipefail
cd "$(dirname "$0")"
ep=$1
S=~/.claude/skills/xilo-opus-video/scripts
dur=$(python3 -c "import json;print(json.load(open('$ep/script.json'))['duration'])")
python3 build.py "$ep"
ffmpeg -v error -y -i "$ep/audio/mix.wav" -af "aresample=192000,volume=6.8dB,alimiter=limit=0.72:attack=1:release=80:level=disabled,aresample=48000" "$ep/audio/mix_norm.wav"
python3 "$S/render.py" "$ep/index.html" "out/miveh-star-$ep.mp4" --size 1080x1920 --fps 30 --duration "$dur" --audio "$ep/audio/mix_norm.wav"

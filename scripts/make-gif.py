"""Story 39: joins the screencast frames from record-media.mjs into the
README's GIF (docs/media/autopilot.gif), then deletes the frames.

    python scripts/make-gif.py [speed] [width]

speed: playback speed-up (default 2); width: GIF width in px (default 800).
Needs Pillow (pip install pillow).
"""

import json
import shutil
import sys
import tempfile
from pathlib import Path

from PIL import Image

media = Path(__file__).resolve().parent.parent / "docs" / "media"
frames_dir = Path(tempfile.gettempdir()) / "mission-control-frames"
speed = float(sys.argv[1]) if len(sys.argv) > 1 else 2.0
width = int(sys.argv[2]) if len(sys.argv) > 2 else 800
fps = 8

timings = json.loads((frames_dir / "timings.json").read_text())
start, end = timings[0]["t"], timings[-1]["t"]

# Chrome sends a frame only when something changes; resample to a steady
# frame rate, holding the last frame through quiet stretches.
images, durations = [], []
step = 1 / fps
t, i = start, 0
while t <= end:
    while i + 1 < len(timings) and timings[i + 1]["t"] <= t:
        i += 1
    frame = Image.open(frames_dir / timings[i]["file"]).convert("RGB")
    frame = frame.resize((width, round(frame.height * width / frame.width)), Image.LANCZOS)
    if images and list(frame.getdata()) == list(images[-1].getdata()):
        durations[-1] += step / speed
    else:
        images.append(frame)
        durations.append(step / speed)
    t += step

# One shared palette keeps the cockpit colors steady between frames.
palette = images[len(images) // 2].quantize(colors=128, method=Image.Quantize.MEDIANCUT)
gif = [im.quantize(palette=palette, dither=Image.Dither.NONE) for im in images]

out = media / "autopilot.gif"
gif[0].save(
    out,
    save_all=True,
    append_images=gif[1:],
    duration=[max(20, round(d * 1000)) for d in durations],
    loop=0,
    optimize=True,
)
shutil.rmtree(frames_dir, ignore_errors=True)

print(f"{out.name}: {len(gif)} frames, {sum(durations):.1f} s, {out.stat().st_size / 1e6:.1f} MB")

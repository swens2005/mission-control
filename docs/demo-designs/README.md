# Proofmark demo designs

The sources of the demo images in `resources/demo/proofmark/` (story 19).
They are plain HTML and CSS, so they can be changed and rendered again.
Only the WebP files ship; this folder is not deployed.

To regenerate:

1. Open each `.html` file in Chrome (headless is fine) at its real width:
   1440 px for desktop pages, 390 px for mobile pages, device pixel ratio 1.
2. Save a full-page screenshot as `<same name>.png` into one folder.
3. From the repo root, run `php docs/demo-designs/to-webp.php <that folder>`.
   It writes the WebP files at quality 80 and prints their sizes.

`DemoProofmarkTest` checks that every shipped image passes the same
validation as an upload and that together they stay under 600 KB.

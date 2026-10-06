# 0009. Design uploads: re-encode with GD, private disk, shared demo files

- Status: accepted
- Date: 2026-10-06

## Context

Proofmark is the first feature that stores files people upload. The demo
makes anyone on the internet an uploader, so the files must be treated as
hostile:

- **Active content.** An SVG can carry JavaScript; a "PNG" can really be
  HTML or PHP, and a browser that sniffs it may run it.
- **Hidden data.** Photos and exports carry EXIF (camera, GPS, author) and
  can hide extra bytes after the image data (polyglot files).
- **Resource exhaustion.** A tiny file can declare 50 000 × 50 000 pixels;
  decoding it would need gigabytes of memory on a shared host.
- **Disk.** Up to 200 demo sandboxes can exist at once (`demo.max_active`),
  on a host with a disk quota.

The host has GD and Imagick. CI has GD only. PHP's limits on the host are
1 GB for uploads, posts and memory, so the app sets its own limits.

## Decision

- **Formats:** PNG, JPEG and WebP only, recognized by their magic bytes and
  confirmed by `getimagesize`. The file name and the browser's MIME type are
  ignored.
- **Limits before decoding:** 8 MB per file, 16 megapixels, 10 000 px on
  the longest side. `getimagesize` reads only the header, so the limits are
  checked before any pixels are decoded.
- **Re-encode with GD:** every image is decoded into pixels and written to
  a new file of the same type (JPEG and WebP at quality 85, PNG with its
  alpha channel). Nothing from the original file survives except the
  pixels, so metadata and appended bytes are gone. Images wider than
  2560 px are scaled down. GD rather than Imagick because CI can test it,
  and its small feature set is a smaller attack surface.
- **Storage:** the private `local` disk, at
  `proofmark/{workspace_id}/{random}.{ext}`. Never in `public/`, never
  under the uploaded name.
- **Serving:** one authorized route behind `auth`, which checks the design's
  policy and streams the file with its stored content type,
  `X-Content-Type-Options: nosniff`, `Content-Disposition: inline` and
  `Cache-Control: private`.
- **Quota:** sizes are stored with each design, so the quota is a database
  sum, not a disk scan. 20 MB per sandbox and 1 GB across all sandboxes
  (`config/demo.php`). Real workspaces have no app-level limit.
- **Cleanup:** `sandbox:prune` deletes each pruned workspace's folder and
  sweeps folders whose workspace no longer exists.
- **Demo designs:** shipped in the repo as small WebP files
  (`resources/demo/proofmark/`). Seeded designs point to those files
  instead of copying them, so a new sandbox writes nothing to disk.

## Consequences

- A visitor can't put anything on the server that a browser would run, and
  can't use more than 20 MB of disk.
- Re-encoding costs a little quality, and colour profiles are lost (GD
  ignores ICC profiles). Good enough for reviewing layouts, which is what
  Proofmark is for.
- Animated WebP isn't supported (GD can't decode it); the upload is refused
  with a plain message.

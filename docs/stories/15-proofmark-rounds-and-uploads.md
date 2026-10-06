# 15. Proofmark: design rounds and safe uploads

As a studio admin, I want to upload a project's designs in numbered rounds,
so the client always reviews one clear set of screens, and nothing unsafe or
private ends up on the server.

## Acceptance criteria

- [ ] A project has **review rounds** (v1, v2, …), at
      `/admin/projects/{project}/proofmark`, opened from the project page
      ("Proofmark"). A new round starts as a **draft**.
- [ ] A round holds one or more **designs**: a title ("Home, desktop") and
      one image. Designs in a draft round can be renamed, reordered and
      removed.
- [ ] **Uploads accept PNG, JPG and WebP only**, checked by the file's
      content (magic bytes and `getimagesize`), never by its name or the
      browser's MIME type. SVG, GIF, HEIC and anything else are refused with
      a plain message.
- [ ] **Limits**, checked before decoding: at most 8 MB per file, 16
      megapixels, and 10 000 px on the longest side (so a "decompression
      bomb" can't exhaust PHP's memory on the shared host).
- [ ] Every image is **re-encoded with GD** into a fresh file of the same
      type. This drops EXIF and every other metadata block (camera, GPS,
      comments) and anything appended after the image data. Images wider
      than 2560 px are scaled down to 2560 px.
- [ ] Files go to a **private disk** (`storage/app/private/proofmark/{workspace}/`)
      under a random name. The original file name is kept only as text.
- [ ] Images are served by an **authorized route** behind `auth`: an admin
      of the workspace, or a client contact of the project's organization
      once the round is sent (story 16). Others get 404. The response sets
      the stored content type, `X-Content-Type-Options: nosniff`,
      `Content-Disposition: inline` and `Cache-Control: private`.
- [ ] **Disk quota:** a sandbox may store at most 20 MB of uploads, and all
      sandboxes together at most 1 GB (config in `demo.php`). Over the
      limit, the upload is refused with a friendly message.
- [ ] **`sandbox:prune` deletes a sandbox's upload folder** with the
      workspace, and sweeps any folder whose workspace no longer exists.
- [ ] Tables `review_rounds` and `designs` use `BelongsToWorkspace`;
      `ReviewRoundPolicy` reuses `AdminOfWorkspace` and
      `ProjectPolicy::viewAsClient`.
- [ ] Every write calls `Activity::record()` (studio-only while a draft).
- [ ] The page uses the cockpit look from the start: a "light table" of
      `lc-card`s with `lc-label`s ("● 01 · HOME, DESKTOP"), the round number
      in the HUD style. The upload field is a native file input with a
      visible label, hint and error (`FormField`).
- [ ] `data-tour`: `open-proofmark`, `new-round`, `upload-design`,
      `round-list`.

## Tests

- [ ] PNG, JPG and WebP uploads are stored re-encoded; a JPEG with EXIF
      (including GPS) comes back without it.
- [ ] SVG, a PHP file renamed to `.png`, a GIF, a too-large file and an
      oversized image are refused.
- [ ] The image route: 200 for the admin, 404 for another workspace, 404
      for a client before the round is sent and for another organization.
- [ ] The sandbox quota refuses the upload that would exceed it.
- [ ] `sandbox:prune` removes the expired sandbox's files and orphaned
      folders, and leaves the others alone.
- [ ] Unit: the upload limits and the scale-down maths.

## Notes

- GD, not Imagick: GD is enabled on the host and in CI, Imagick only on
  the host. See ADR 0009.
- Production's `upload_max_filesize` and `post_max_size` must be at least
  8 MB; to confirm in cPanel before this ships.

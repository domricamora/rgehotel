# Hero background video

Drop the homepage hero video here, then point **Admin → Settings → Hero background video**
at it (e.g. `video/hero.mp4`).

## Recommended encode
- **Format:** MP4 (H.264 / AAC) — widest browser support. Optionally also provide `hero.webm` (VP9).
- **Resolution:** 1920×1080 (1080p). The CSS scales it to cover the hero.
- **Length:** a seamless 10–20s loop.
- **Audio:** none / muted (the tag is muted so it can autoplay).
- **Weight:** keep under ~5–8 MB so it loads fast; the still poster shows until it’s ready.

## Fallback
The hero always renders the **Hero image** (Setting `hero_image`, default `general/hero-island`)
as the poster and as the background when no video is set or `prefers-reduced-motion` is on.
So a higher-quality Kalanggaman still belongs in the normal image pipeline (see
`scripts/image-normalize.php`, the `general` map), and the video is layered on top.

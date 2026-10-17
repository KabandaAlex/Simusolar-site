This folder stores files uploaded via the Admin panel.

Structure:
  uploads/
    videos/   - Hero videos (e.g. home page background). Path saved in pages.hero_video_path
    images/   - Product images, section images. Paths saved in products.image_path, page_sections.image_path

Paths are stored in the database relative to the site root (e.g. "uploads/videos/hero_abc123.mp4").
The website uses these paths to build the full URL so uploaded content displays correctly.

All uploads are saved inside this site folder and served by the same web server.

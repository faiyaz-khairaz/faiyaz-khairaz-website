# faiyazkhairaz.com

Personal website of **Faiyaz M Khairaz**, Microsoft Certified Trainer and Freelance AI Trainer (Copilot, Claude, Advanced Excel and Power BI).

A single-page site: plain HTML, CSS and JavaScript, plus one PHP file for the enquiry form. No frameworks and no build step.

## What's in this repository

| Path | What it is |
|---|---|
| `index.html` | The whole website (page, styles and scripts) |
| `send-enquiry.php` | Emails enquiry-form submissions to the three addresses set at the top of the file |
| `robots.txt`, `sitemap.xml` | Files that help Google find and index the site |
| `assets/img/` | Headshot, certificates, video thumbnails, share image (`og-image.jpg`) and tab icon |
| `assets/logos/` | Client logos, named as in the logo wall (for example `adani-group.png`) |
| `assets/gallery/` | Training photos, `1.jpg` to `16.jpg` |

## Publishing

Upload everything in this repository except `README.md` to the `public_html` folder of the hosting account.

The host must support **PHP** for the enquiry form to send emails (Hostinger, GoDaddy and other cPanel hosting do; GitHub Pages and Netlify do not). Create the mailbox `connect@faiyazkhairaz.com` in the hosting panel, because the form sends from that address.

## Common updates

- **Add a gallery photo:** add `17.jpg`, `18.jpg` and so on to `assets/gallery/`, and add a matching entry in the gallery block of `index.html`.
- **Add a client logo:** add the PNG to `assets/logos/`, and add a tile to the logo wall in `index.html`.
- **Change who receives enquiries:** edit the `$recipients` list at the top of `send-enquiry.php`.

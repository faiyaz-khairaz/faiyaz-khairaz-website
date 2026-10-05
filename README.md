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
| `case-studies/` | Case study pages: `index.html` lists them all (with Topic and Industry filters), one page per case study, shared `case-studies.css` and `case-studies.js` |
| `assets/case-studies/` | Photos and video covers used by the case studies |

## Publishing

Upload everything in this repository except `README.md` to the `public_html` folder of the hosting account.

The host must support **PHP** for the enquiry form to send emails (Hostinger, GoDaddy and other cPanel hosting do; GitHub Pages and Netlify do not). Create the mailbox `connect@faiyazkhairaz.com` in the hosting panel, because the form sends from that address.

## Common updates

- **Add a gallery photo:** add `17.jpg`, `18.jpg` and so on to `assets/gallery/`, and add a matching entry in the gallery block of `index.html`.
- **Add a client logo:** add the PNG to `assets/logos/`, and add a tile to the logo wall in `index.html`.
- **Change who receives enquiries:** edit the `$recipients` list at the top of `send-enquiry.php`.

## Adding a case study

1. Name it **topic-industry-short-name**, for example `excel-manufacturing-mis-dashboards.html`, and copy an existing case study page in `case-studies/` as the starting point.
2. Put its photos and video cover in `assets/case-studies/`, using the same name as the start of each file name.
3. Add a card to `case-studies/index.html`. Set `data-topic` (for example `ai`, `excel`, `power-bi`) and `data-industry` (for example `banking`, `supply-chain`). If it is a new topic or industry, add a matching filter button.
4. On the homepage, the Case Studies section shows the latest two or three. Swap the oldest card for the new one.
5. Add the page to `sitemap.xml`.

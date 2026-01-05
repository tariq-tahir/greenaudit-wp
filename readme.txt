=== GreenAudit WP ===
Contributors: Tariq Tahir
Tags: sustainability, carbon footprint, green web, eco-friendly, website audit, performance, accessibility, PDF report, climate action
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Measure, reduce, and report your website’s carbon footprint — directly from your WordPress dashboard.

== Description ==

🌍 **GreenAudit WP** is a technical sustainability toolkit for WordPress — empowering developers, agencies, and eco-conscious site owners to quantify and lower their digital carbon emissions.

Unlike generic carbon calculators, GreenAudit integrates real-time metrics (page weight, energy efficiency, hosting type) with authoritative data from the [Website Carbon Calculator API](https://www.websitecarbon.com), then generates **professional, downloadable PDF audit reports** — ideal for compliance, client deliverables, and ESG reporting.

✅ **Key Features**
- 🔍 One-click carbon footprint analysis per page/post
- 📊 Detailed metrics: CO₂ per page view, energy rating, data transfer
- 📄 Automated PDF reports (using dompdf) — branded, printable, shareable
- ⚙️ Admin dashboard with site-wide sustainability score
- 🌱 Actionable recommendations to reduce impact (e.g., image optimization, caching)
- 🌐 Multilingual-ready (POT included)

💡 **Why It Matters**  
The internet produces ~1.6 billion tonnes of CO₂ yearly — more than most countries. GreenAudit makes sustainability *measurable* and *actionable* — aligning with global climate goals and ethical web development.

Built for transparency, security, and scalability — no external tracking, no data collection.

== Installation ==

1. Go to **Plugins → Add New** in your WordPress admin.
2. Click **Upload Plugin**, then choose `greenaudit.zip`.
   *(Or manually upload the `greenaudit` folder to `/wp-content/plugins/` via FTP.)*
3. Activate the plugin.
4. Visit the new **GreenAudit** menu item in the left sidebar to run your first audit.

== Frequently Asked Questions ==

= Does this plugin send my data to third parties? =
No. Page analysis is done via an anonymous API call (only URL + page size sent — no personal/user data). PDF generation happens server-side.

= Can I customize the PDF report? =
Yes. The plugin uses a modular template system — developers can override report templates via your theme.

= Is this plugin compatible with caching/CDN? =
Yes. GreenAudit measures the *actual served* page size — so results reflect your live optimizations.

= Can I use this for client reporting? =
Absolutely. The PDF includes your site name, date, and metrics — ready to include in sustainability or performance reviews.

== Screenshots ==

1. Admin dashboard showing site-wide carbon score
2. Per-page audit results with energy rating
3. Sample PDF report (clean, professional layout)
4. Recommendations panel with actionable tips

== Changelog ==

= 1.0.0 =
* Official stable release
* Added PDF report generation (dompdf)
* Improved caching resilience
* Full i18n support (POT file included)
* Security hardening: capability checks, input sanitization

= 0.4.0 =
* Added per-post audit capability
* Energy efficiency rating (A–G scale)
* Admin UI refresh

= 0.3.0 =
* WordPress.org compliance update
* License updated to GPLv3+

= 0.2.0 =
* Initial beta release

== Upgrade Notice ==

= 1.0.0 =
This update includes critical security improvements and PDF reporting. Backup before upgrading.

== Third Party Libraries ==

This plugin bundles:
- **dompdf** (v2.0.3) — for PDF generation  
  License: LGPL-2.1-or-later  
  Source: https://github.com/dompdf/dompdf  

All bundled libraries are unmodified and used in compliance with their licenses.

== Credits ==
- Carbon calculation methodology: [Website Carbon Calculator](https://www.websitecarbon.com)  
- Icons: [Heroicons](https://heroicons.com) (MIT)

== Author ==
Developed by Tariq Tahir — advocate for ethical, sustainable technology.  
🌐 https://ecowebtools.org  
✉️ tariq@ecowebtools.org  
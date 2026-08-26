<?php
namespace App\Controllers\Public;

use App\Core\Controller;
use App\Models\RoomType;
use App\Models\Content;
use App\Models\Setting;

/**
 * Dynamic XML sitemap — regenerates from published content on each request.
 */
class SitemapController extends Controller
{
    public function index(): string
    {
        $urls = [];
        $add = function (string $path, string $priority = '0.6', string $freq = 'weekly', ?string $lastmod = null) use (&$urls) {
            $urls[] = ['loc' => site_url($path), 'priority' => $priority, 'freq' => $freq, 'lastmod' => $lastmod];
        };

        // Core pages
        $add('/', '1.0', 'daily');
        $add('/accommodations', '0.9', 'daily');
        $add('/services', '0.8', 'weekly');
        $add('/packages', '0.8', 'weekly');
        $add('/offers', '0.7', 'weekly');
        $add('/reviews', '0.6', 'weekly');
        $add('/about', '0.5', 'monthly');
        $add('/the-resort', '0.7', 'monthly');
        $add('/contact', '0.5', 'monthly');
        if (Setting::get('restaurant_published', '0') === '1') {
            $add('/restaurant', '0.6', 'weekly');
        }

        // Detail pages
        foreach (RoomType::published() as $r) {
            $add('/accommodations/' . $r['slug'], '0.8', 'weekly', $r['updated_at'] ?? null);
        }
        foreach (Content::services() as $s) {
            $add('/services/' . $s['slug'], '0.6', 'monthly', $s['updated_at'] ?? null);
        }
        foreach (Content::packages() as $p) {
            $add('/packages/' . $p['slug'], '0.6', 'monthly', $p['updated_at'] ?? null);
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>';
            if (!empty($u['lastmod'])) {
                $xml .= '<lastmod>' . substr((string) $u['lastmod'], 0, 10) . '</lastmod>';
            }
            $xml .= '<changefreq>' . $u['freq'] . '</changefreq>';
            $xml .= '<priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo $xml;
        exit;
    }

    /**
     * RSS 2.0 feed of published content. Doubles as an alternate sitemap
     * (search engines accept RSS feeds) and is consumable by feed readers.
     */
    public function rss(): string
    {
        $items = [];
        $add = function (string $path, string $title, string $desc = '', ?string $date = null) use (&$items) {
            $items[] = [
                'loc'   => site_url($path),
                'title' => $title,
                'desc'  => $desc,
                'date'  => $date,
            ];
        };

        // Detail pages first (most "content-like"), then core pages.
        foreach (RoomType::published() as $r) {
            $add('/accommodations/' . $r['slug'], $r['name'], (string) ($r['summary'] ?? ''), $r['updated_at'] ?? null);
        }
        foreach (Content::packages() as $p) {
            $add('/packages/' . $p['slug'], $p['name'], (string) ($p['summary'] ?? ''), $p['updated_at'] ?? null);
        }
        foreach (Content::services() as $s) {
            $add('/services/' . $s['slug'], $s['name'], (string) ($s['summary'] ?? ''), $s['updated_at'] ?? null);
        }
        $add('/offers', 'Special Offers & Promos', 'Early-bird discounts, stay-longer deals and seasonal promos.');
        $add('/accommodations', 'Accommodations', 'Rooms and suites at RGE Hotel near Kalanggaman Island.');
        if (Setting::get('restaurant_published', '0') === '1') {
            $add('/restaurant', 'Restaurant', 'Fresh seafood and Filipino favourites by the beach.');
        }

        $now = date('r');
        $self = site_url('/sitemap.rss');
        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        $xml .= '  <channel>' . "\n";
        $xml .= '    <title>RGE Hotel — Beachfront Escape near Kalanggaman Island, Leyte</title>' . "\n";
        $xml .= '    <link>' . htmlspecialchars(site_url('/'), ENT_XML1) . '</link>' . "\n";
        $xml .= '    <description>Rooms, packages, island-hopping tours and special offers at RGE Hotel, Palompon, Leyte.</description>' . "\n";
        $xml .= '    <language>en-PH</language>' . "\n";
        $xml .= '    <lastBuildDate>' . $now . '</lastBuildDate>' . "\n";
        $xml .= '    <atom:link href="' . htmlspecialchars($self, ENT_XML1) . '" rel="self" type="application/rss+xml"/>' . "\n";
        foreach ($items as $it) {
            $pub = !empty($it['date']) ? date('r', strtotime((string) $it['date'])) : $now;
            $xml .= '    <item>' . "\n";
            $xml .= '      <title>' . htmlspecialchars($it['title'], ENT_XML1) . '</title>' . "\n";
            $xml .= '      <link>' . htmlspecialchars($it['loc'], ENT_XML1) . '</link>' . "\n";
            $xml .= '      <guid isPermaLink="true">' . htmlspecialchars($it['loc'], ENT_XML1) . '</guid>' . "\n";
            if ($it['desc'] !== '') {
                $xml .= '      <description>' . htmlspecialchars($it['desc'], ENT_XML1) . '</description>' . "\n";
            }
            $xml .= '      <pubDate>' . $pub . '</pubDate>' . "\n";
            $xml .= '    </item>' . "\n";
        }
        $xml .= '  </channel>' . "\n";
        $xml .= '</rss>' . "\n";

        header('Content-Type: application/rss+xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');
        echo $xml;
        exit;
    }
}

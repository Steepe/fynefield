<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Imports only missing reference content and replaces known placeholder images. */
class ImportRedesignContent extends Migration
{
    public function up()
    {
        $manifestPath = FCPATH . 'assets/redesign/manifest.json';
        $sourcePath = FCPATH . 'assets/redesign/fynefield-redesign.html';
        if (! is_file($manifestPath) || ! is_file($sourcePath)) throw new \RuntimeException('The redesign assets are missing.');
        $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
        $resources = [];
        foreach ($manifest['resources'] as $resource) $resources[$resource['id']] = $manifest['paths'][$resource['uuid']] ?? '';
        $replacements = [
            ['posts', 'slug', 'fynefield-petroleum-recognized-at-otl', 'cover_image', '/assets/media/frontpage-image1.jpg', 'r_station_busy'],
            ['posts', 'slug', 'state-of-the-art-petroleum-depot', 'cover_image', '/assets/media/big-1.jpg', 'r_depot_wide'],
            ['services', 'slug', 'logistics', 'image', '/assets/media/frontpage-image4.jpg', 'r_tanker_road'],
            ['services', 'slug', 'retail-outlets', 'image', '/assets/media/oil-small.jpg', 'r_station_forecourt'],
        ];
        foreach ($replacements as [$table, $key, $value, $column, $old, $asset]) {
            if (! empty($resources[$asset])) $this->db->table($table)->where([$key => $value, $column => $old])->update([$column => $resources[$asset]]);
        }

        $source = file_get_contents($sourcePath);
        if (! preg_match('~<script type="__bundler/template">\s*(.*?)\s*</script>~s', $source, $match)) throw new \RuntimeException('The redesign template is missing.');
        $template = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        if (! preg_match('~class NewsLogic extends Sub.*?const list = (\[.*?\]);\s*const all = list\.map~s', $template, $match)) throw new \RuntimeException('The redesign news content is missing.');
        $articles = json_decode($match[1], true, 512, JSON_THROW_ON_ERROR);
        $article = $articles[0] ?? null;
        if ($article && ! $this->db->table('posts')->groupStart()->where('slug', 'cftz-award-and-new-stations')->orWhere('title', $article['title'])->groupEnd()->countAllResults()) {
            $now = date('Y-m-d H:i:s');
            $this->db->table('posts')->insert([
                'title' => $article['title'], 'slug' => 'cftz-award-and-new-stations', 'excerpt' => $article['excerpt'],
                'content' => implode("\n\n", $article['body']), 'category' => $article['category'], 'quote' => $article['quote'],
                'author' => 'Fynefield Petroleum', 'cover_image' => $resources['r_station_opening'] ?? '',
                'status' => 'published', 'published_at' => date('Y-m-d H:i:s', strtotime($article['date'])), 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        // Reference assets are available to editors in the Studio media picker.
        foreach (['r_station_opening' => 'Guests at a Fynefield filling station opening', 'r_station_busy' => 'Fynefield filling station serving customers', 'r_depot_wide' => 'Storage tanks at the Fynefield depot', 'r_tanker_road' => 'Fynefield tank truck on the road', 'r_station_forecourt' => 'Fynefield filling station forecourt', 'r_depot_aerial' => 'Aerial view of the Fynefield depot', 'r_team' => 'Fynefield team at a filling station', 'r_fleet' => 'Fynefield fleet of tank trucks'] as $id => $alt) {
            $path = $resources[$id] ?? '';
            if ($path === '' || $this->db->table('media')->where('path', $path)->countAllResults()) continue;
            $file = FCPATH . ltrim($path, '/');
            $dimensions = is_file($file) ? @getimagesize($file) : false;
            $this->db->table('media')->insert(['path' => $path, 'filename' => basename($path), 'mime_type' => 'image/jpeg', 'alt_text' => $alt, 'folder' => 'Operations', 'size_bytes' => is_file($file) ? filesize($file) : 0, 'width' => $dimensions[0] ?? null, 'height' => $dimensions[1] ?? null, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function down()
    {
        // Content and editorial changes are retained on rollback.
    }
}

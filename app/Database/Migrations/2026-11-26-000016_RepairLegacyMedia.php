<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RepairLegacyMedia extends Migration
{
    public function up()
    {
        $manifest = json_decode(file_get_contents(FCPATH . 'assets/redesign/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
        $assets = [];
        foreach ($manifest['resources'] as $resource) $assets[$resource['id']] = $manifest['paths'][$resource['uuid']] ?? '';
        $map = [
            'aboutus.jpg' => 'r_depot_aerial', 'big-1.jpg' => 'r_depot_wide', 'big-2.jpg' => 'r_station_busy',
            'cards-background.jpg' => 'r_depot_gantry', 'energy-small.jpg' => 'r_depot_tanks',
            'frontpage-image1.jpg' => 'r_station_busy', 'frontpage-image2.jpg' => 'r_depot_wide', 'frontpage-image3.jpg' => 'r_team',
            'frontpage-image4.jpg' => 'r_tanker_road', 'frontpage-image5.jpg' => 'r_station_forecourt', 'frontpage-image6.jpg' => 'r_loading_bay',
            'oil-small.jpg' => 'r_station_busy', 'recent-posts-background.jpg' => 'r_depot_aerial', 'services-header-background.jpg' => 'r_fleet', 'slider3.jpg' => 'r_depot_aerial',
        ];
        $replace = [];
        foreach ($map as $file => $resource) if (! empty($assets[$resource])) $replace['/assets/media/' . $file] = $assets[$resource];
        $walk = static function ($value) use (&$walk, $replace) {
            if (is_array($value)) return array_map($walk, $value);
            return is_string($value) && isset($replace[$value]) ? $replace[$value] : $value;
        };
        foreach ($this->db->table('pages')->select('id,sections')->get()->getResultArray() as $page) {
            $sections = json_decode((string) $page['sections'], true);
            if (! is_array($sections)) continue;
            $updated = $walk($sections);
            if ($updated !== $sections) $this->db->table('pages')->where('id', $page['id'])->update(['sections' => json_encode($updated, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
        }
        // Only blank descriptions are filled; editor-supplied descriptions win.
        $descriptions = ['/assets/media/Emmanuel-Ibekwe.jpg' => 'Portrait of Emmanuel Ibekwe', '/assets/media/Gabriel-Ogbechie-copy.jpg' => 'Portrait of Gabriel Ogbechie'];
        foreach ($map as $file => $_) $descriptions['/assets/media/' . $file] = 'Legacy image placeholder showing dimensions; replace before use';
        foreach ($descriptions as $path => $alt) {
            $this->db->table('media')->where('path', $path)->groupStart()->where('alt_text', null)->orWhere('alt_text', '')->groupEnd()->update(['alt_text' => $alt]);
        }
    }

    public function down()
    {
        // Preserve editorial content on rollback.
    }
}

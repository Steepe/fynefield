<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CmsEnhancementSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $items = [
            ['Home', '/'], ['About', '/about-us'], ['Services', '/services'], ['News', '/news'], ['Leadership', '/management-team'], ['Contact us', '/contact-us'],
        ];
        foreach ($items as $position => [$label, $url]) {
            $existing = $this->db->table('navigation_items')->where('url', $url)->get()->getRowArray();
            $data = ['label' => $label, 'url' => $url, 'sort_order' => $position + 1, 'is_active' => 1, 'updated_at' => $now];
            if ($existing) $this->db->table('navigation_items')->where('id', $existing['id'])->update($data);
            else $this->db->table('navigation_items')->insert($data + ['created_at' => $now]);
        }
    }
}

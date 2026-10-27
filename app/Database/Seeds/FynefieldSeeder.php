<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class FynefieldSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('users')->insert([
            'name' => 'Fynefield Administrator', 'email' => 'admin@fynefield.com',
            'password_hash' => password_hash('ChangeMe!2026', PASSWORD_DEFAULT), 'role' => 'admin', 'must_change_password' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $settings = [
            'site_name' => 'Fynefield Petroleum',
            'site_tagline' => 'Energy solutions built on service excellence.',
            'contact_email' => 'info@fynefield.com',
            'contact_phone' => '+234 807 119 1635',
            'address' => 'Suite 3, 4th Floor, Sinoki House, P.M.B 603, Ahmadu Bello Way, CBD, Abuja.',
            'footer_text' => 'Fynefield Petroleum Company Limited is a Nigerian vertically integrated oil and gas downstream company.',
            'logo' => '/assets/media/fynefield-logo.png',
        ];
        foreach ($settings as $key => $value) $this->db->table('settings')->insert(['setting_key' => $key, 'setting_value' => $value, 'updated_at' => $now]);
        $this->db->table('pages')->insertBatch([
            ['title' => 'About Fynefield', 'slug' => 'about-us', 'excerpt' => 'A Nigerian downstream energy company built for dependable supply and service.', 'content' => '<p>Fynefield Petroleum Company Limited is a Nigerian vertically integrated Oil and Gas Downstream company, incorporated on 31 January 2013.</p><p>Our operations span petroleum product trading, bulk storage, haulage and distribution, and retail outlets. We bring disciplined operations and customer focus to every link in the downstream value chain.</p>', 'cover_image' => '/assets/media/aboutus.jpg', 'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Management Team', 'slug' => 'management-team', 'excerpt' => 'The people guiding Fynefield.', 'content' => '<p>Our leadership team combines practical energy-sector experience with a commitment to safe, reliable service.</p>', 'cover_image' => '/assets/media/fynefield-commissioning.jpeg', 'status' => 'published', 'published_at' => $now, 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->db->table('services')->insertBatch([
            ['title' => 'Bulk Storage', 'slug' => 'bulk-storage', 'summary' => 'Multi-product depot capacity supporting reliable petroleum product availability.', 'content' => '<p>Our 50-million-litre capacity multi-product depot is located in the Calabar Free Trade Zone, Cross River State.</p>', 'image' => '/assets/media/fynefield-commissioning.jpeg', 'sort_order' => 1, 'status' => 'published', 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Logistics & Distribution', 'slug' => 'logistics', 'summary' => 'Efficient delivery across our station network and customer locations.', 'content' => '<p>We provide essential product haulage services through a dedicated fleet serving Calabar, Delta and Abuja.</p>', 'image' => '/assets/media/frontpage-image4.jpg', 'sort_order' => 2, 'status' => 'published', 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'Retail Outlets', 'slug' => 'retail-outlets', 'summary' => 'Convenient access to dependable PMS across key Nigerian locations.', 'content' => '<p>Our growing retail network spans Cross River, Akwa Ibom, Delta, Benue, Borno and the FCT.</p>', 'image' => '/assets/media/oil-small.jpg', 'sort_order' => 3, 'status' => 'published', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->db->table('team_members')->insertBatch([
            ['name' => 'Gabriel Ogbechie', 'position' => 'Chairman', 'bio' => 'Guiding Fynefield\'s long-term direction and commitment to excellence.', 'image' => '/assets/media/Gabriel-Ogbechie-copy.jpg', 'sort_order' => 1, 'status' => 'published', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Emmanuel Ibekwe', 'position' => 'Management Team', 'bio' => 'Driving dependable operations across the downstream value chain.', 'image' => '/assets/media/Emmanuel-Ibekwe.jpg', 'sort_order' => 2, 'status' => 'published', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->db->table('locations')->insert(['name' => 'Abuja Office', 'address' => 'Suite 3, 4th Floor, Sinoki House, P.M.B 603, Ahmadu Bello Way, CBD, Abuja.', 'city' => 'Abuja', 'state' => 'FCT', 'phone' => '+234 807 119 1635', 'status' => 'published', 'created_at' => $now, 'updated_at' => $now]);
        $this->db->table('posts')->insertBatch([
            ['title' => 'Fynefield Petroleum Recognized at OTL', 'slug' => 'fynefield-petroleum-recognized-at-otl', 'excerpt' => 'Fynefield Petroleum was recognized for sponsorship and customer engagement at the Oil Trading and Logistics Expo.', 'content' => '<p>Fynefield Petroleum continues to support conversations that move Nigeria\'s downstream energy sector forward.</p>', 'cover_image' => '/assets/media/frontpage-image1.jpg', 'status' => 'published', 'published_at' => '2024-01-12 09:00:00', 'created_at' => $now, 'updated_at' => $now],
            ['title' => 'A State-of-the-Art Petroleum Depot', 'slug' => 'state-of-the-art-petroleum-depot', 'excerpt' => 'How strategic storage infrastructure improves product availability and service reliability.', 'content' => '<p>Reliable energy supply begins with infrastructure designed for safety, efficiency and scale.</p>', 'cover_image' => '/assets/media/big-1.jpg', 'status' => 'published', 'published_at' => '2024-01-10 09:00:00', 'created_at' => $now, 'updated_at' => $now],
        ]);
        $this->call('LegacyPageSeeder');
        $this->call('CmsEnhancementSeeder');
    }
}

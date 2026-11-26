<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('healthz', 'Site::health');
$routes->get('sitemap.xml', 'Site::sitemap');
$routes->get('robots.txt', 'Site::robots');
// The reference design's links are intercepted during normal navigation, but
// must also work when opened in a new tab or copied directly.
foreach (['Home' => '/', 'About' => 'about-us', 'Services' => 'services', 'Leadership' => 'management-team', 'News' => 'news', 'Contact' => 'contact-us'] as $reference => $target) {
    $routes->get($reference . '.dc.html', static fn () => redirect()->to(site_url($target), 301));
}

// Sign-in and account recovery.
$routes->get('admin/login', 'Auth::login');
$routes->post('admin/login', 'Auth::attempt');
$routes->get('admin/forgot-password', 'Auth::forgotPassword');
$routes->post('admin/forgot-password', 'Auth::sendPasswordReset');
$routes->get('admin/reset-password', 'Auth::resetPassword');
$routes->post('admin/reset-password', 'Auth::completePasswordReset');
$routes->post('admin/logout', 'Auth::logout', ['filter' => 'adminauth']);
$routes->get('admin/profile', 'Admin::profile', ['filter' => 'adminauth']);
$routes->post('admin/profile', 'Admin::saveProfile', ['filter' => 'adminauth']);

// Fynefield Studio: one page application plus its JSON API.
$routes->group('admin', ['filter' => 'adminauth'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Studio::index');
    $routes->get('inquiries/export', 'Studio::exportInquiries');
    $routes->group('api', static function (RouteCollection $routes): void {
        $routes->post('pages', 'Studio::createPage');
        $routes->post('pages/(:num)/draft', 'Studio::saveDraft/$1');
        $routes->post('pages/(:num)/publish', 'Studio::publishPage/$1');
        $routes->post('pages/(:num)/duplicate', 'Studio::duplicatePage/$1');
        $routes->post('pages/(:num)/delete', 'Studio::deletePage/$1');
        $routes->post('pages/(:num)/preview-link', 'Studio::previewLink/$1');
        $routes->get('pages/(:num)/revisions', 'Studio::revisions/$1');
        $routes->post('pages/(:num)/revisions/(:num)/restore', 'Studio::restoreRevision/$1/$2');
        $routes->post('collections/(:segment)', 'Studio::saveItem/$1');
        $routes->post('collections/(:segment)/(:num)', 'Studio::saveItem/$1/$2');
        $routes->post('collections/(:segment)/(:num)/delete', 'Studio::deleteItem/$1/$2');
        $routes->post('collections/(:segment)/reorder', 'Studio::reorderItems/$1');
        $routes->post('media', 'Studio::uploadMedia');
        $routes->post('media/(:num)', 'Studio::saveMedia/$1');
        $routes->post('media/(:num)/replace', 'Studio::replaceMedia/$1');
        $routes->post('media/(:num)/delete', 'Studio::deleteMedia/$1');
        $routes->post('inquiries/(:num)', 'Studio::saveInquiry/$1');
        $routes->post('inquiries/(:num)/reply', 'Studio::replyInquiry/$1');
        $routes->post('nav', 'Studio::saveNav');
        $routes->post('theme', 'Studio::saveTheme');
        $routes->post('settings/(:segment)', 'Studio::saveSettings/$1');
        $routes->post('users', 'Studio::inviteUser');
        $routes->post('users/(:num)', 'Studio::saveUser/$1');
        $routes->post('users/(:num)/delete', 'Studio::deleteUser/$1');
        $routes->post('perms', 'Studio::savePerms');
    });
});

// Public site. Every page renders from its Studio sections.
$routes->group('', ['filter' => 'sitestate'], static function (RouteCollection $routes): void {
    $routes->get('/', 'Site::home');
    $routes->get('services/(:segment)', 'Site::service/$1');
    $routes->get('news/(:segment)', 'Site::article/$1');
    $routes->post('contact-us', 'Site::storeInquiry');
    $routes->post('newsletter', 'Site::subscribe');
    $routes->post('unlock/(:segment)', 'Site::unlockPage/$1');
    $routes->get('preview/news/(:segment)', 'Site::previewPost/$1');
    $routes->get('preview/(:segment)', 'Site::previewPage/$1');
    $routes->get('(:segment)', 'Site::page/$1');
    $routes->get('(:any)', 'Site::legacyRedirect/$1');
});

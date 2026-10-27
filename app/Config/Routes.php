<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Site::home');
$routes->get('about-us', 'Site::page/about-us');
$routes->get('management-team', 'Site::page/management-team');
$routes->get('services', 'Site::services');
$routes->get('services/(:segment)', 'Site::service/$1');
$routes->get('news', 'Site::news');
$routes->get('news/(:segment)', 'Site::article/$1');
$routes->get('contact-us', 'Site::contact');
$routes->post('contact-us', 'Site::storeInquiry');
$routes->get('admin/login', 'Auth::login');
$routes->post('admin/login', 'Auth::attempt');
$routes->post('admin/logout', 'Auth::logout', ['filter' => 'adminauth']);
$routes->get('admin', 'Admin::dashboard', ['filter' => 'adminauth']);
$routes->get('admin/settings', 'Admin::settings', ['filter' => 'adminauth']);
$routes->post('admin/settings', 'Admin::saveSettings', ['filter' => 'adminauth']);
$routes->get('admin/media', 'Admin::media', ['filter' => 'adminauth']);
$routes->post('admin/media', 'Admin::upload', ['filter' => 'adminauth']);
$routes->get('admin/(:segment)', 'Admin::index/$1', ['filter' => 'adminauth']);
$routes->get('admin/(:segment)/new', 'Admin::create/$1', ['filter' => 'adminauth']);
$routes->get('admin/(:segment)/(:num)/edit', 'Admin::edit/$1/$2', ['filter' => 'adminauth']);
$routes->post('admin/(:segment)', 'Admin::save/$1', ['filter' => 'adminauth']);
$routes->post('admin/(:segment)/(:num)', 'Admin::save/$1/$2', ['filter' => 'adminauth']);
$routes->post('admin/(:segment)/(:num)/delete', 'Admin::delete/$1/$2', ['filter' => 'adminauth']);

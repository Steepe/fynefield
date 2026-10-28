<?php

namespace App\Filters;

use App\Libraries\Cms;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Runs before every public page: promotes scheduled page drafts whose time has
 * come, and shows the maintenance message to visitors while maintenance mode is on.
 */
class SiteStateFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        Cms::applyScheduled();
        $advanced = Cms::get('advanced');
        if (! $advanced['maintenance'] || session('admin_id')) return null;
        $general = Cms::get('general');
        $html = view('site/maintenance', ['general' => $general, 'message' => (string) $advanced['maintenanceMsg'], 'theme' => Cms::get('theme')]);
        return service('response')->setStatusCode(503)->setHeader('Retry-After', '3600')->setBody($html);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}

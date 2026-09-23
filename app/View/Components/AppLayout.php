<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * The page container's max width. Individual pages should NOT wrap
     * their own content in a max-w/mx-auto/px-8 div — that width is
     * enforced once here so it can't drift page-to-page. 'default' covers
     * ordinary listing/dashboard pages; 'narrow' is for single-column
     * forms (edit pages, create-record forms) where full width would just
     * be wasted space either side of the form; 'wide' is for pages that
     * need more than the default's 1360px, e.g. the similarity-report page
     * laying two full submission texts side by side. The top nav bar stays
     * fixed at 1360px regardless — see layouts/navigation.blade.php.
     */
    public function __construct(
        public string $maxWidth = 'default',
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}

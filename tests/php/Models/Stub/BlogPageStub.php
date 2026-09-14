<?php

namespace XD\PageContentBlock\Tests\Models\Stub;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\TestOnly;

/**
 * A page whose short name ("BlogPageStub") does not match any shipped
 * ancestry template, mimicking a project page (e.g. a Blog) that supplies its
 * own XD\PageContentBlock\Models\BlogPageStubContentBlock.ss. Rendering that
 * template outside a page request is what used to fatal during search indexing.
 */
class BlogPageStub extends SiteTree implements TestOnly
{
    private static $table_name = 'PageContentBlockTest_BlogPageStub';
}

<?php

namespace XD\PageContentBlock\Tests\Models;

use DNADesign\Elemental\Extensions\ElementalPageExtension;
use ReflectionProperty;
use SilverStripe\Dev\SapphireTest;
use XD\PageContentBlock\Models\PageContentBlock;
use XD\PageContentBlock\Tests\Models\Stub\BlogPageStub;

class PageContentBlockTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected static $extra_dataobjects = [
        BlogPageStub::class,
    ];

    protected static $required_extensions = [
        BlogPageStub::class => [
            ElementalPageExtension::class,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Elemental keeps a private static eager-load cache of ElementalAreas.
        // DB ids restart per test, so a value cached by an earlier test could
        // otherwise leak in. Reset it to guarantee each test sees its own data.
        $prop = new ReflectionProperty(ElementalPageExtension::class, 'elementalAreas');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /**
     * Regression test for the crash reported when a search index is built for a
     * page containing a PageContentBlock: getElementsForSearch() -> the block's
     * getContentForSearchIndex() used to render the page ancestry template
     * outside a page request, which fatals when that template's data is null.
     * It must now return the page's plain-text content without throwing.
     */
    public function testGetElementsForSearchDoesNotThrow(): void
    {
        $page = $this->createPageWithContentBlock('Latest news', '<p>Intro paragraph.</p>');

        $search = $page->getElementsForSearch();

        $this->assertIsString($search);
        $this->assertStringContainsString('Latest news', $search);
        $this->assertStringContainsString('Intro paragraph.', $search);
    }

    public function testGetContentForSearchIndexReturnsPlainText(): void
    {
        $page = $this->createPageWithContentBlock('Plain page', '<p>Hello <strong>world</strong>.</p>');

        /** @var PageContentBlock $block */
        $block = $page->ElementalArea()->Elements()->first();

        $content = $block->getContentForSearchIndex();

        $this->assertStringContainsString('Plain page', $content);
        $this->assertStringContainsString('Hello', $content);
        $this->assertStringContainsString('world', $content);
        // Tags are stripped rather than rendered as markup.
        $this->assertStringNotContainsString('<strong>', $content);
        $this->assertStringNotContainsString('<p>', $content);
    }

    public function testGetContentForSearchIndexWithoutPageReturnsEmptyString(): void
    {
        $block = PageContentBlock::create();
        $block->write();

        // No parent area/page: must degrade to an empty string, never fatal.
        $this->assertSame('', $block->getContentForSearchIndex());
    }

    private function createPageWithContentBlock(string $title, string $content): BlogPageStub
    {
        $page = BlogPageStub::create();
        $page->Title = $title;
        $page->Content = $content;
        // Writing the page makes elemental create its ElementalArea has_one.
        $page->write();

        $block = PageContentBlock::create();
        $block->write();
        $page->ElementalArea()->Elements()->add($block);

        // Reload so the block resolves its owner page via the persisted relation.
        return BlogPageStub::get()->byID($page->ID);
    }
}

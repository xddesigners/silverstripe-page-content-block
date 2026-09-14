<?php

namespace XD\PageContentBlock\Models;

use DNADesign\Elemental\Models\BaseElement;
use DNADesign\ElementalUserForms\Control\ElementFormController;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;

class PageContentBlock extends BaseElement
{
    private static $table_name = 'PageContentBlock';

    private static $singular_name = 'Page content block';

    private static $plural_name = 'Page content blocks';

    private static $icon = 'font-icon-block-content';

    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Page content');
    }

    public function getTitle()
    {
        return $this->getType();
    }

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $notice = _t(
            __CLASS__ . '.About',
            'This block holds the content of the parent page. To edit, simply edit the parent\'s content field'
        );

        $fields->addFieldsToTab('Root.Main', [
            LiteralField::create('Notification', "<p class='message notice'>{$notice}</p>"),
        ]);

        $fields->removeByName('Title');
        return $fields;
    }

    public function forTemplate($holder = true): string
    {
        if (!$page = $this->getPage()) {
            return '';
        }

        // detect virtual page and replace parent
        if ($page instanceof VirtualPage) {
            $page = $page->CopyContentFrom();
        }

        if (!$page) {
            return '';
        }

        $controller = $this->getRenderController($page);

        // Place the full ancestry in the current namespace so templates are to be placed in a coherent place
        $nameSpace = __NAMESPACE__;
        $sliceAncestry = array_map(function ($item) use ($nameSpace) {
            $name = ClassInfo::shortName($item);
            return "{$nameSpace}\\{$name}ContentBlock";
        }, array_reverse($page->getClassAncestry()));

        return $controller->renderWith($sliceAncestry, ['Block' => $this]);
    }

    /**
     * Resolve a controller to render the page ancestry templates against.
     *
     * During a normal front-end request the current controller is the page's
     * own ContentController and is reused as-is, so request driven features
     * such as pagination keep working (reusing Controller::curr() is why the
     * "always build a PageController" approach was reverted previously).
     *
     * Outside of a page request the current controller is either an
     * ElementFormController (user form submission), some unrelated controller,
     * or null altogether - e.g. a search index build via
     * {@see \DNADesign\Elemental\Extensions\ElementalPageExtension::getElementsForSearch()},
     * a BuildTask or any other CLI context. In those cases Controller::curr()
     * cannot provide this page's scope, so a fresh controller for the page is
     * created; this prevents both a "method call on null" fatal and the page
     * data being null while rendering the template.
     */
    protected function getRenderController(DataObject $page): Controller
    {
        $current = Controller::curr();

        $isElementFormController = class_exists(ElementFormController::class)
            && $current instanceof ElementFormController;

        if ($current instanceof ContentController && !$isElementFormController) {
            return $current;
        }

        $controllerClass = $page->getControllerName();
        /** @var ContentController $controller */
        $controller = $controllerClass::create($page);

        // Only carry over the request when there is a live one; building a
        // controller with an empty request is what previously broke pagination.
        if ($current) {
            $controller->setRequest($current->getRequest());
        }

        return $controller;
    }

    /**
     * Provide plain-text content for the search index without rendering the
     * page ancestry template.
     *
     * The {@see BaseElement::getContentForSearchIndex()} default renders
     * forTemplate(), which for this block renders the parent page's (project
     * supplied) ancestry template. Those templates assume a live page
     * request/controller scope, so rendering them while building a search
     * index (Solr/Elastic/DB fulltext via ElementalPageExtension::
     * getElementsForSearch()), from a BuildTask, or any other non-request
     * context can fatal - e.g. an `<% include %>`/loop that receives null
     * data. As this block merely mirrors the parent page's own content, the
     * indexable text is taken straight from the page instead.
     */
    public function getContentForSearchIndex(): string
    {
        $content = '';

        if ($page = $this->getPage()) {
            if ($page instanceof VirtualPage) {
                $page = $page->CopyContentFrom();
            }

            if ($page) {
                $parts = [];
                foreach (['Title', 'Content'] as $field) {
                    $value = $page->hasField($field) ? (string) $page->getField($field) : '';
                    if ($value !== '') {
                        $parts[] = $value;
                    }
                }

                // Strip tags but keep a space between words, matching BaseElement.
                $content = trim(strip_tags(str_replace('<', ' <', implode(' ', $parts))));
            }
        }

        // Allow projects to augment/replace the indexable content, e.g. a blog
        // page appending its linked post titles. Mirrors BaseElement behaviour.
        $this->extend('updateContentForSearchIndex', $content);

        return $content;
    }

    protected function provideBlockSchema()
    {
        $blockSchema = parent::provideBlockSchema();
        $blockSchema['content'] = _t(
            __CLASS__ . '.About',
            'This block holds the content of the parent page. To edit, simply edit the parent\'s content field'
        );
        return $blockSchema;
    }
}

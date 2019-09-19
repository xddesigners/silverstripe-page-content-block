<?php

namespace XD\PageContentBlock\Models;

use DNADesign\Elemental\Models\BaseElement;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\CMS\Model\VirtualPage;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\LiteralField;

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

    public function forTemplate($holder = true)
    {
        $controller = Controller::curr();
        $page = $this->getPage();

        if (!$page) {
            return null;
        }

        // detect virtual page and replace parent
        if ($page instanceof VirtualPage) {
            $page = $page->CopyContentFrom();
        }

        // Place the full ancestry in the current namespace so templates are to be placed in a coherent place
        $nameSpace = __NAMESPACE__;
        $sliceAncestry = array_map(function ($item) use ($nameSpace) {
            $name = ClassInfo::shortName($item);
            return "{$nameSpace}\\{$name}ContentBlock";
        }, array_reverse($page->getClassAncestry()));

        return $controller->renderWith($sliceAncestry, ['Block' => $this]);
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
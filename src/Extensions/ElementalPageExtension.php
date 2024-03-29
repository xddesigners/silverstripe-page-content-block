<?php

namespace XD\PageContentBlock\Extensions;

use DNADesign\Elemental\Extensions\ElementalPageExtension as OriginalElementalPageExtension;
use DNADesign\Elemental\Models\BaseElement;
use DNADesign\Elemental\Models\ElementalArea;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HTMLEditor\HTMLEditorField;
use XD\PageContentBlock\Models\PageContentBlock;

/**
 * @property SiteTree|ElementalPageExtension owner
 * @method ElementalArea ElementalArea
 * @property ElementalArea ElementalArea
 */
class ElementalPageExtension extends OriginalElementalPageExtension
{
    private static $keep_content_field = true;

    public function updateCMSFields(FieldList $fields)
    {
        $fields = parent::updateCMSFields($fields);

        // Global keep content field setting
        $keepContentField = Config::forClass(self::class)->get('keep_content_field');

        // Per class hide content field setting
        $hideContentField = $this->owner->config()->get('hide_content_field');

        if ($keepContentField && !$hideContentField && $fields) {
            // Reinsert the Content field
            $fields->insertAfter('MenuTitle', $htmlField = HTMLEditorField::create(
                'Content',
                _t(__CLASS__ . '.HTMLEDITORTITLE', 'Content', 'HTML editor title')
            )->addExtraClass('stacked'));

            // Move the Elemental area
            if( $field = $fields->fieldByName('Root.Main.ElementalArea') ) {
                $fields->addFieldToTab('Root.Layout', $field);
            }
        }
        
        return $fields;
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (!$this->owner->supportsElemental()) {
            return;
        }

        if ($defaultBlocks = $this->owner->config()->get('default_blocks')) {
            $blocks = array_unique($defaultBlocks);
        }

        // Remove elements that are in dissallowed elements
        if ($disallowedElements = $this->owner->config()->get('disallowed_elements')) {
            $blocks = array_diff($blocks, $disallowedElements);
        }

        // Remove elements that are not in allowed elementes
        if ($allowedElements = $this->owner->config()->get('allowed_elements')) {
            $blocks = array_intersect($allowedElements, $blocks);
        }

        if (!empty($blocks) && ($area = $this->owner->ElementalArea()) && ($elements = $area->Elements()) && !$elements->exists()) {
            foreach ($blocks as $blockClass) {
                $validClasses = ClassInfo::getValidSubClasses(BaseElement::class);
                if (in_array($blockClass, $validClasses)) {
                    $block = $blockClass::create();
                    $block->write();
                    $elements->add($block);
                    $block->publishSingle();
                }
            }
        }
    }
    
    public function updateAvailableTypesForClass($class, &$list)
    {
        if (
            ($area = $this->owner->ElementalArea()) &&
            $count = $area->Elements()->filter('ClassName', PageContentBlock::class)->count()
        ) {
            if (isset($list[PageContentBlock::class])) {
                unset($list[PageContentBlock::class]);
            }
        }
    }
}

# Page Content Block

Adds a page content block to the elemental editor.

### What it is

A Page content block is used to display the content from the Page model in the elemental area. 
This is useful for instance on pages like BlogPost's, UserForms or any page type that is added by a module.

### For who it exists

For example, users editing blog posts want a lighter editing experience, but keep the flexibility of a block editor.
This module sits in between by re-inserting the content field and moving the Elemental area to a Layout tab.   

### What it does

It simply renders the current page controller in an Element. 
So you can access the page `$Content`, `$Title` or any other method on that element.
The `PageContentBlock` is created on page creation so the element always exists. 
The user can simply start typing in the content field and keeps the flexibility of adding any blocks around the `PageContentBlock`.

### Configuration

The creation of the `PageContentBlock` is configurable, this module looks on the current page type for a config setting `default_blocks`. 
You could also use that setting to create a default banner block on `BlogPosts`.
```yml
Page:
  default_blocks:
    - XD\PageContentBlock\Models\PageContentBlock

BlogPost:
  default_blocks:
    - MyFeaturedImageBlockClass
    - XD\PageContentBlock\Models\PageContentBlock
```

If you don't want the re-inserting of the content field you can set the `keep_content_field` setting to `false`.
```yml
XD\PageContentBlock\Extensions\ElementalPageExtension:
  keep_content_field: false
```

If you want to disable the re-inserting of the content field on a specific class, for example the home page. 
```yml
HomePage:
  hide_content_field: true
```
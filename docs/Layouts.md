# Transform layouts

If you use some specific image transforms in multiple template files and want to avoid code duplication, you can define them in the plugin config file, along with the breakpoint values. To do that, use `transformLayouts` setting:

```php
'transformLayouts' => [
    'someHandle' => [
        'variants' => [
            [
                'media' => '(max-width: 999px)',
                'transform' => [
                    'width' => 300,
                    'mode' => 'crop',
                ]
            ],
            [
                'media' => '(min-width: 1000px)',
                'transform' => [
                    'width' => 600,
                    'mode' => 'stretch',
                ]
            ]               
        ],
        'attributes' => [
            'class' => 'some-class'
        ],
    ],
],
```

Our transform layout handle is `someHandle`. Here's how to use it in the template:

```twig
{{craft.images.layout([assetForVariant1, assetForVariant2], 'someHandle')}}
```

As you can see, we passed array of two assets to the method (one for each variant), without defining any other settings within Twig - everything else is defined in the config file. If you want, you can also pass only one asset to the function - it will be reused for every variant defined in the `variants` file.

```twig
{{craft.images.layout(someAsset, 'someHandle')}}
```
HTLM attributes of `<picture>` element can be hard-coded in the `attributes` array of the transform layout, or can be outputted dynamically, using the  anonymous function. Anonymous function returns array of attributes and takes in parameter containing all assets that are passed to `layout()` method.

```php
'transformLayouts' => [
    'someHandle' => [
        'variants' => [
            [
                'media' => '(max-width: 999px)',
                'transform' => [
                    'width' => 300,
                    'mode' => 'crop',
                ]
            ],
            [
                'media' => '(min-width: 1000px)',
                'transform' => [
                    'width' => 600,
                    'mode' => 'stretch',
                ]
            ]               
        ],
        'attributes' => function($assets){
            if(!is_null($assets[0])){
                if($assets[0]->getFieldValue('someField') != ''){
                    $title = $assets[0]->getFieldValue('someField');
                }else{
                    $title = $assets[0]->title;
                }
            }else{
                $title = null;
            }
            $attrs = [
                'title' => $title;
            ];
            return $attrs;
        },
    ],
],
```

In the example above, we are using first asset passed to `layout` method to determine value of title attribute used on `<picture>`. If the field with `someField` handle which is assigned to asset is not empty, this field is used for title. If it is empty, `title` attribute of asset is used instead. And if asset is missing, we just use `null` which will result in `title` attribute not being added to the `<picture>` at all.
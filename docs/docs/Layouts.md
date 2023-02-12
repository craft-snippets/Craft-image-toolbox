# Transform layouts

If you use specific image transforms in multiple template files, you can set them in config file, along with breakpoint values. To do this, use `transformLayouts` setting:

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
        ]
    ]
]
```

Our transform layout handle is `someHandle`. Here's how to use it in template:

```twig
{{craft.images.layout(someAsset, 'someHandle')}}
```

Transform layouts can also be used to output `<picture>` with single variant. Just use only single variant and ommit `media`: 

```php
'transformLayouts' => [
    'someOtherHandle' => [
        'variants' => [
            [
                'transform' => [
                    'width' => 300,
                    'mode' => 'crop',
                ]
            ]           
        ],
        'attributes' => [
            'class' => 'some-class'
        ]
    ]
]
```
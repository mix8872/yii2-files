Yii2-files module
=================

Module for attach any files to the your models.

**This module is a new version of [`mix8872/files-attacher`](https://github.com/mix8872/files-attacher) and not compatible with it!  
You may use old mix8872/files-attacher or completely delete it and install this module.  
The migration mechanism is not provided.**

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist mix8872/yii2-files
```

or add

```
"mix8872/yii2-files": "~1.0"
```

to the `require` section of your `composer.json`.

Then you must run migration by running command:

yii migrate --migrationPath=@vendor/mix8872/yii2-files/src/migrations

Configure
----------

To configure module please add following to the modules section of common main config:

Common:

```php
'modules' => [
	'yiiFiles' => [
		'class' => 'mix8872\yiiFiles\Module',
		'as access' => [
			'class' => 'yii\filters\AccessControl',
			'rules' => [
				[
					'controllers' => ['yiiFiles/default'],
					'allow' => true,
					'roles' => ['admin']
				],
			]
		],
		'parameters' => [ // general module parameters
            'sizes' => [ // if imgProcessDriver is GD supports only jpeg, png and gif
                'galleryMiddle' => ['width' => '880', 'height' => '587', 'model' => ['common\modules\imageslider\models\ImageSlider']],
                'galleryPreview' => ['width' => '120', 'height' => '60', 'model' => ['common\modules\imageslider\models\ImageSlider']]
            ],
            'sizesNameBy' => 'template', // or 'key', optional, default 'size'
            'sizesNameTemplate' => '_resize_%k-%s', //optional, if sizesNameBy set to 'template'
            'imgProcessDriver' => 'gd', //or 'imagick', optional, default 'gd',
            'filesNameBy' => 'translit', // optional, by default naming is random string
            'savePath' => '/uploads/attachments/', // optional, default save path is '/uploads/attachments/'
        ],
        'attributes' => [ // general attributes configuration, optional
            'preview' => [
                'filetypes' => ['image/*'],
                'resize' => ['width' => '1024', 'height' => '768'], //optional, if imgProcessDriver is GD supports only jpeg, png and gif
            ],
            'images' => [
                'multiple' => true,
                'filetypes' => ['image/*'],
            ]
        ]
	],
	// ... other modules definition
],
```

Frontend: 

```
'modules' => [
        ...
        
        'yiiFiles' => [
            'as access' => [
                'class' => \yii\filters\AccessControl::class,
                'rules' => [
                    [
                        'allow' => false,
                    ],
                ],
            ],
        ],
    ],
```
## Important!!!
#### Don't forget deny access to module from frontend app!!!


In config you may define access control to prevent access to the administrative part of the module.

Also you can define `imageResize` to create additional sizes for uploaded images.

In `imageResize` definitions, also you can optional define model for which scaling will be applied. Support definition several models as array.

To use sizes names template you may define `sizesNameTemplate` option, where `%k` - key, `%s` - size. By default - `%s`;

If `origResize` option defined original image size will be changed. Also you can define models array;

Also you can change image driver to imagick.

By define `filesNameBy` option you may change files naming style from random string to translit file name, also you can define `model` attribute too.

For changing default save path you can define `savePath` option. The path will be considered from the web directory.

Usage
-----

Using the module is available as a widget and behavior for the model.

First, you must configure the behavior of the required models in this way:

In tags attribute you may define tags for attach files, if you define same tags in delteOld attribute then files loaded with this tags will be rewritten by newly added files.

Also you can configure attachment parameters directly in behavior:

```php
public function behaviors()
    {
        return [
            'FileAttachBehavior' => [
                'class' => \mix8872\yiiFiles\behaviors\FileAttachBehavior::class,
                'attributes' => [
                    'images' => [
                        'multiple' => true, // optional, default false. allow multiple loading
                        'filetypes' => ['image/*'], // array of mime types of allowed files
                        'resize' => ['width' => '1024', 'height' => '768'], //optional, for images only
                    ],
                    'videos' => [

                    ]
                ],
            ],
            // ... other behaviors
        ];
    }
```

Any way you must declare fileAttachBehavior with this name `files`:

```php
public function behaviors()
    {
        return [
            'files' => [
                'class' => \mix8872\yiiFiles\behaviors\FileAttachBehavior::class,
                ...
            ],
            // ... other behaviors
        ];
    }
```

Next you may add widget model and echo widget with its config:

```php
use mix8872\yiiFiles\widgets\FilesWidget;

?>

<!-- ... some view code -->

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>
<!-- ['options' => ['enctype' => 'multipart/form-data']] - is IMPORTANT
 if you use widget as stay alone widget instead input widget --> 
 
 ...

     <!-- Preferably widget declaration -->
     <?= $form->field($model, 'images')->widget(FilesWidget::class, [ 
         'theme' => FilesWidget::THEME_BROWSE, // optional, or THEME_DRAGDROP or THEME_BROWSE_DRAGDROP
         'width' => '100%', // optional, width of dragDrop zone
         'height' => '100px', // optional, height of dragDrop zone
         'options' => ['class' => 'some-custom-class'] // optional, custom input options
     ])->label('Custom label') ?>
	
    <!-- OR -->

    <?= FilesWidget::widget([
        'model' => $model,
        'attribute' => 'videos', // one of the tags listed in the model
        'theme' => FilesWidget::THEME_BROWSE, // or THEME_DRAGDROP or THEME_BROWSE_DRAGDROP
        'width' => '100%', // optional, width of dragDrop zone
        'height' => '100px', // optional, height of dragDrop zone
        'options' => ['class' => 'some-custom-class'] // optional, custom input options
    ]) ?>

...

<?php ActiveForm::end() ?>
```

**!!! IMPORTANT !!!**  
**You may define form with `['options' => ['enctype' => 'multipart/form-data']]`
if you use stay alone widget instead input widget!**

If uses input widget - `multipart/form-data` automatically added to you form.

Also you can attach file to model by url as follows:
```php
$model->attachByUrl(string $tag, string $url);
```

You can get the model files by calling the method:
```php
$files = $model->getFiles('property'); //array of file objects

// public function getFiles(string $property, bool $single, bool $asQuery)
```
- $property - tag of you attachment
- $single - if true - returns single attachment object, default false
- $asQuery - if true - returns ActiveQuery object, default false

or by accessing as property:

```php
$files = $model->property;
```

If uses getting files as property then you get array of files objects if property is multiple or single file object if not

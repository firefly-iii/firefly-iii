<?php

/*
|--------------------------------------------------------------------------
| EnvironmentFilter
|--------------------------------------------------------------------------
|
| This is used to run filters on specific environments. For example, if you
| only want to run a filter on production and staging environments
|
| new EnvironmentFilter(new FilterExample, App::environment(), array('production', 'staging')),
|
*/
use Codesleeve\AssetPipeline\Filters\EnvironmentFilter;

return array(

	/*
	|--------------------------------------------------------------------------
	| routing array
	|--------------------------------------------------------------------------
	|
	| This is passed to the Route::group and allows us to group and filter the
	| routes for our package
	|
	*/
	'routing' => array(
		'prefix' => '/assets'
	),

	/*
	|--------------------------------------------------------------------------
	| paths
	|--------------------------------------------------------------------------
	|
	| These are the directories we search for files in.
	|
	| NOTE that the '.' in require_tree . is relative to where the manifest file
	| (i.e. app/assets/javascripts/application.js) is located
	|
	*/
	'paths' => array(
		'app/assets/javascripts',
		'app/assets/stylesheets',
		'app/assets/images',
		'lib/assets/javascripts',
		'lib/assets/stylesheets',
		'lib/assets/images',
		'provider/assets/javascripts',
		'provider/assets/stylesheets',
		'provider/assets/images'
	),

	/*
	|--------------------------------------------------------------------------
	| mimes
	|--------------------------------------------------------------------------
	|
	| In order to know which mime type to send back to the server
	| we need to know if it is a javascript or stylesheet type. If
	| the extension is not found below then we just return a regular
	| download.
	|
	*/
	'mimes' => array(
	    'javascripts' => array('.js', '.js.coffee', '.coffee', '.html', '.min.js'),
	    'stylesheets' => array('.css', '.css.less', '.css.sass', '.css.scss', '.less', '.sass', '.scss', '.min.css'),
	),

	/*
	|--------------------------------------------------------------------------
	| filters
	|--------------------------------------------------------------------------
	|
	| In order for a file to be included with sprockets, it needs to be listed
	| here and we can also do any preprocessing on files with the extension if
	| we choose to.
	|
	*/
	'filters' => array(
		'.min.js' => array(

		),
		'.min.css' => array(
			new Codesleeve\AssetPipeline\Filters\URLRewrite(App::make('url')->to('/')),
		),
		'.js' => array(
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\JSMinPlusFilter, App::environment()),
		),
		'.js.coffee' => array(
			new Codesleeve\AssetPipeline\Filters\CoffeeScript,
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\JSMinPlusFilter, App::environment()),
		),
		'.coffee' => array(
			new Codesleeve\AssetPipeline\Filters\CoffeeScript,
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\JSMinPlusFilter, App::environment()),
		),
		'.css' => array(
			new Codesleeve\AssetPipeline\Filters\URLRewrite(App::make('url')->to('/')),
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\CssMinFilter, App::environment()),
		),
		'.css.less' => array(
			new Codesleeve\AssetPipeline\Filters\LessphpFilter,
			new Codesleeve\AssetPipeline\Filters\URLRewrite(App::make('url')->to('/')),
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\CssMinFilter, App::environment()),
		),
		'.css.sass' => array(
			new Codesleeve\AssetPipeline\Filters\SassFilter,
			new Codesleeve\AssetPipeline\Filters\URLRewrite(App::make('url')->to('/')),
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\CssMinFilter, App::environment()),
		),
		'.css.scss' => array(
			new Assetic\Filter\ScssphpFilter,
			new Codesleeve\AssetPipeline\Filters\URLRewrite(App::make('url')->to('/')),
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\CssMinFilter, App::environment()),
		),
		'.less' => array(
			new Codesleeve\AssetPipeline\Filters\LessphpFilter,
			new Codesleeve\AssetPipeline\Filters\URLRewrite(App::make('url')->to('/')),
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\CssMinFilter, App::environment()),
		),
		'.sass' => array(
			new Codesleeve\AssetPipeline\Filters\SassFilter,
			new Codesleeve\AssetPipeline\Filters\URLRewrite(App::make('url')->to('/')),
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\CssMinFilter, App::environment()),
		),
		'.scss' => array(
			new Assetic\Filter\ScssphpFilter,
			new Codesleeve\AssetPipeline\Filters\URLRewrite(App::make('url')->to('/')),
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\CssMinFilter, App::environment()),
		),
		'.html' => array(
			new Codesleeve\AssetPipeline\Filters\JST,
			new EnvironmentFilter(new Codesleeve\AssetPipeline\Filters\JSMinPlusFilter, App::environment()),
		)
	),

	/*
	|--------------------------------------------------------------------------
	| cache
	|--------------------------------------------------------------------------
	|
	| By default we cache assets on production environment permanently. We also cache
	| all files using the `cache_server` driver below but the cache is busted anytime
	| those files are modified. On production we will cache and the only way to bust
	| the cache is to delete files from app/storage/cache/asset-pipeline or run a
	| command php artisan assets:clean -f somefilename.js -f application.css ...
	|
	*/
	'cache' => 	array('production'),

	/*
	|--------------------------------------------------------------------------
	| cache_server
	|--------------------------------------------------------------------------
	|
	| You can create your own CacheInterface if the filesystem cache is not up to
	| your standards. This is for caching asset files on the server-side.
	|
	| Please note that caching is used on **ALL** environments always. This is done
	| to increase performance of the pipeline. Cached files will be busted when the
	| file changes.
	|
	| However, manifest files are regenerated (not cached) when the environment is
	| not found within the 'cache' array. This lets you develop on local and still
	| utilize caching, so you don't have to regenerate all precompiled files while
	| developing on your assets.
	|
	| See more in CacheInterface.php at
	|
	|    https://github.com/kriswallsmith/assetic/blob/master/src/Assetic/Cache
	|
	|
	*/
	'cache_server' => new Assetic\Cache\FilesystemCache(App::make('path.storage') . '/cache/asset-pipeline'),

	/*
	|--------------------------------------------------------------------------
	| cache_client
	|--------------------------------------------------------------------------
	|
	| If you want to handle 304's and what not, to keep users from refetching
	| your assets and saving your bandwidth you can use a cache_client driver
	| that handles this. This doesn't handle assets on the server-side, use
	| cache_server for that. This only works when the current environment is
	| listed within `cache`
	|
	| Note that this needs to implement the interface
	|
	|	Codesleeve\Sprockets\Interfaces\ClientCacheInterface
	|
	| or this won't work correctly. It is a wrapper class around your cache_server
	| driver and also uses the AssetCache class to help access files.
	|
	*/
	'cache_client' => new Codesleeve\AssetPipeline\Filters\ClientCacheFilter,

	/*
	|--------------------------------------------------------------------------
	| concat
	|--------------------------------------------------------------------------
	|
	| This allows us to turn on the asset concatenation for specific
	| environments listed below. You can turn off local environment if
	| you are trying to troubleshoot, but you will likely have better
	| performance if you leave concat on (except if you are doing a lot
	| of minification stuff on each page refresh)
	|
	*/
	'concat' => array('production', 'local'),

	/*
	|--------------------------------------------------------------------------
	| directives
	|--------------------------------------------------------------------------
	|
	| This allows us to turn completely control which directives are used
	| for the sprockets parser that asset pipeline uses to parse manifest files.
	|
	| It is probably safe just to leave this alone unless you are familar with
	| what is actually going on here.
	|
	*/
	'directives' => array(
		'require ' => new Codesleeve\Sprockets\Directives\RequireFile,
		'require_directory ' => new Codesleeve\Sprockets\Directives\RequireDirectory,
		'require_tree ' => new Codesleeve\Sprockets\Directives\RequireTree,
		'require_tree_df ' => new Codesleeve\Sprockets\Directives\RequireTreeDf,
		'require_self' => new Codesleeve\Sprockets\Directives\RequireSelf,
		'include ' => new Codesleeve\Sprockets\Directives\IncludeFile,
		'include_directory ' => new Codesleeve\Sprockets\Directives\IncludeDirectory,
		'include_tree ' => new Codesleeve\Sprockets\Directives\IncludeTree,
		'stub ' => new Codesleeve\Sprockets\Directives\Stub,
		'depend_on ' => new Codesleeve\Sprockets\Directives\DependOn,
	),

	/*
	|--------------------------------------------------------------------------
	| javascript_include_tag
	|--------------------------------------------------------------------------
	|
	| This allows us to completely control how the javascript_include_tag function
	| works for asset pipeline.
	|
	| It is probably safe just to leave this alone unless you are familar with
	| what is actually going on here.
	|
	*/
	'javascript_include_tag' => new Codesleeve\AssetPipeline\Composers\JavascriptComposer,

	/*
	|--------------------------------------------------------------------------
	| stylesheet_link_tag
	|--------------------------------------------------------------------------
	|
	| This allows us to completely control how the stylesheet_link_tag function
	| works for asset pipeline.
	|
	| It is probably safe just to leave this alone unless you are familar with
	| what is actually going on here.
	|
	*/
	'stylesheet_link_tag' => new Codesleeve\AssetPipeline\Composers\StylesheetComposer,

	/*
	|--------------------------------------------------------------------------
	| image_tag
	|--------------------------------------------------------------------------
	|
	| This allows us to completely control how the image_tag function
	| works for asset pipeline.
	|
	| It is probably safe just to leave this alone unless you are familar with
	| what is actually going on here.
	|
	*/
	'image_tag' => new Codesleeve\AssetPipeline\Composers\ImageComposer,

	/*
	|--------------------------------------------------------------------------
	| controller_action
	|--------------------------------------------------------------------------
	|
	| Asset pipeline will route all requests through the controller action
	| listed here. This allows us to completely control how the controller
	| should behave for incoming requests for assets.
	|
	| It is probably safe just to leave this alone unless you are familar with
	| what is actually going on here.
	|
	*/
	'controller_action' => '\Codesleeve\AssetPipeline\AssetPipelineController@file',

	/*
	|--------------------------------------------------------------------------
	| sprockets_filter
	|--------------------------------------------------------------------------
	|
	| When concatenation is turned on, when an asset is fetched from the sprockets
	| generator it is filtered through this filter class named below. This allows us
	| to modify the sprockets filter if we need to behave differently.
	|
	| It is probably safe just to leave this alone unless you are familar with
	| what is actually going on here.
	|
	*/
	'sprockets_filter' => '\Codesleeve\Sprockets\SprocketsFilter',

	/*
	|--------------------------------------------------------------------------
	| sprockets_filter
	|--------------------------------------------------------------------------
	|
	| When concatenation is turned on, assets are filtered via SprocketsFilter
	| and we can do global filters on the resulting dump file. This would be
	| useful if you wanted to apply a filter to all javascript or stylesheet files
	| like minification. Out of the box we don't have any filters here. Add at
	| your own risk. I don't put minification filters here because the minify
	| doesn't always work perfectly and can bjork your entire concatenated
	| javascript or stylesheet file if it messes up.
	|
	| It is probably safe just to leave this alone unless you are familar with
	| what is actually going on here.
	|
	*/
	'sprockets_filters' => array(
		'javascripts' => array(),
		'stylesheets' => array(),
	),

);

<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Tests in this suite need to construct MongoDB\BSON\ObjectId and a few other
// BSON types. When the ext-mongodb extension is installed (the production
// environment, the CI runner) the real classes are used. When it isn't (some
// local dev machines, this sandbox), provide minimal runtime-functional
// shims so the tests can still exercise the code under test.
if ( !extension_loaded( 'mongodb' ) ) {
	require __DIR__ . '/Shims/MongoDBShims.php';
}

// Several framework call sites (config::getAppDir, mongodb model meta) reflect
// on \app\app to derive directories. Provide a stub so tests that touch those
// paths can boot, and seed an environmentConfig so config::getEnvironmentConfig
// doesn't try to read a missing JSON file.
if ( !class_exists( '\app\app' ) ) {
	eval( 'namespace app; class app { public static function _before(): void {} public static function _after(): void {} }' );
}

$envConfig = new \gcgov\framework\models\environmentConfig();
$envConfig->basePath = 'api';
$envConfig->appDictionary = [ 'cronMonitorUrl' => 'http://monitor.test/' ];
$prop = new \ReflectionProperty( \gcgov\framework\config::class, 'environmentConfig' );
$prop->setValue( null, $envConfig );

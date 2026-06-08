<?php

declare(strict_types=1);

namespace gcgov\framework\services\authoauth\tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use gcgov\framework\services\authoauth\router;
use gcgov\framework\models\environmentConfig;
use gcgov\framework\models\route;
use gcgov\framework\models\routeHandler;
use gcgov\framework\exceptions\routeException;

#[CoversClass(router::class)]
final class RouterTest extends TestCase {

	protected function setUp(): void {
		$envConfig = new environmentConfig();
		$envConfig->basePath = 'api';

		$prop = new \ReflectionProperty( \gcgov\framework\config::class, 'environmentConfig' );
		$prop->setValue( null, $envConfig );

		unset( $_SERVER[ 'HTTP_AUTHORIZATION' ], $_GET[ 'fileAccessToken' ] );
	}

	public function testRouterImplementsFrameworkRouterInterface(): void {
		$this->assertContains(
			\gcgov\framework\interfaces\router::class,
			class_implements( router::class ) ?: []
		);
	}

	public function testGetRoutesReturnsNineOAuthRoutes(): void {
		$routes = ( new router() )->getRoutes();
		$this->assertCount( 9, $routes );
		foreach ( $routes as $route ) {
			$this->assertInstanceOf( route::class, $route );
		}
	}

	public function testJwksRouteIsPublic(): void {
		$route = $this->findRoute( 'jwks' );
		$this->assertSame( 'GET', $route->httpMethod );
		$this->assertSame( '/api/.well-known/jwks.json', $route->route );
		$this->assertFalse( $route->authentication );
	}

	public function testOpenidConfigurationRouteIsPublic(): void {
		$route = $this->findRoute( 'openid' );
		$this->assertSame( 'GET', $route->httpMethod );
		$this->assertSame( '/api/.well-known/openid-configuration', $route->route );
		$this->assertFalse( $route->authentication );
	}

	public function testFileTokenRouteRequiresAuth(): void {
		$route = $this->findRoute( 'fileToken' );
		$this->assertTrue( $route->authentication );
	}

	public function testAuthorizePostRoute(): void {
		$routes = array_filter( ( new router() )->getRoutes(), fn( $r ) => $r->method === 'oauthPostAuthorize' );
		$this->assertCount( 1, $routes );
		$route = array_values( $routes )[0];
		$this->assertSame( 'POST', $route->httpMethod );
		$this->assertSame( '/api/auth/authorize', $route->route );
		$this->assertFalse( $route->authentication );
	}

	public function testAuthorizeGetRoute(): void {
		$route = $this->findRoute( 'oauthGetAuthorize' );
		$this->assertSame( 'GET', $route->httpMethod );
		$this->assertSame( '/api/auth/authorize', $route->route );
	}

	public function testHybridAuthRouteHasProviderPlaceholder(): void {
		$route = $this->findRoute( 'oauthHybridAuth' );
		$this->assertSame( '/api/auth/hybridauth/{provider}', $route->route );
	}

	public function testVerifyMfaSecretAndCodeRoutesArePostAndAuthenticated(): void {
		$secret = $this->findRoute( 'verifyMfaSecret' );
		$this->assertSame( 'POST', $secret->httpMethod );
		$this->assertTrue( $secret->authentication );

		$code = $this->findRoute( 'verifyMfaCode' );
		$this->assertSame( 'POST', $code->httpMethod );
		$this->assertTrue( $code->authentication );
	}

	public function testAuthenticationWithoutAuthorizationHeaderThrows401(): void {
		$handler = new routeHandler( '\some\controller', 'm' );
		try {
			( new router() )->authentication( $handler );
			$this->fail( 'Expected routeException' );
		}
		catch ( routeException $e ) {
			$this->assertSame( 401, $e->getCode() );
			$this->assertSame( 'Missing Authorization', $e->getMessage() );
		}
	}

	public function testAuthenticationWithShortLivedAllowedButNoTokenThrows(): void {
		$handler = new routeHandler( '\some\controller', 'm' );
		$handler->allowShortLivedUrlTokens = true;
		try {
			( new router() )->authentication( $handler );
			$this->fail( 'Expected routeException' );
		}
		catch ( routeException $e ) {
			$this->assertSame( 401, $e->getCode() );
		}
	}

	public function testAuthenticationRejectsMalformedJwt(): void {
		$_SERVER[ 'HTTP_AUTHORIZATION' ] = 'not.a.valid.jwt';
		$handler = new routeHandler( '\some\controller', 'm' );

		$this->expectException( \Throwable::class );
		( new router() )->authentication( $handler );
	}

	public function testLifecycleHooksReturnVoid(): void {
		router::_before();
		router::_after();
		$ref = new \ReflectionClass( router::class );
		$this->assertSame( 'void', (string) $ref->getMethod( '_before' )->getReturnType() );
		$this->assertSame( 'void', (string) $ref->getMethod( '_after' )->getReturnType() );
	}

	private function findRoute( string $methodName ): route {
		foreach ( ( new router() )->getRoutes() as $route ) {
			if ( $route->method === $methodName ) {
				return $route;
			}
		}
		throw new \LogicException( "No route with method=$methodName" );
	}

}

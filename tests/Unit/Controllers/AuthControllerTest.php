<?php

declare(strict_types=1);

namespace gcgov\framework\services\authoauth\tests\Unit\Controllers;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use gcgov\framework\services\authoauth\controllers\auth;

#[CoversClass(auth::class)]
final class AuthControllerTest extends TestCase {

	public function testControllerImplementsFrameworkControllerInterface(): void {
		$this->assertContains(
			\gcgov\framework\interfaces\controller::class,
			class_implements( auth::class ) ?: []
		);
	}

	public function testConstructorRequiresNoArguments(): void {
		$reflection = new \ReflectionClass( auth::class );
		$constructor = $reflection->getConstructor();
		$this->assertNotNull( $constructor );
		$this->assertSame( 0, $constructor->getNumberOfRequiredParameters() );
		$this->assertInstanceOf( auth::class, new auth() );
	}

	public function testJwksReturnsControllerDataResponseType(): void {
		$reflection = new \ReflectionMethod( auth::class, 'jwks' );
		$this->assertSame(
			\gcgov\framework\models\controllerDataResponse::class,
			(string) $reflection->getReturnType()
		);
	}

	public function testOpenidReturnsControllerDataResponseType(): void {
		$reflection = new \ReflectionMethod( auth::class, 'openid' );
		$this->assertSame(
			\gcgov\framework\models\controllerDataResponse::class,
			(string) $reflection->getReturnType()
		);
	}

	public function testFileTokenReturnsControllerDataResponseType(): void {
		$reflection = new \ReflectionMethod( auth::class, 'fileToken' );
		$this->assertSame(
			\gcgov\framework\models\controllerDataResponse::class,
			(string) $reflection->getReturnType()
		);
	}

	public function testOauthPostAuthorizeMethodExists(): void {
		$this->assertTrue( method_exists( auth::class, 'oauthPostAuthorize' ) );
	}

	public function testOauthGetAuthorizeMethodExists(): void {
		$this->assertTrue( method_exists( auth::class, 'oauthGetAuthorize' ) );
	}

	public function testOauthHybridAuthMethodExists(): void {
		$this->assertTrue( method_exists( auth::class, 'oauthHybridAuth' ) );
	}

	public function testVerifyMfaSecretMethodExists(): void {
		$this->assertTrue( method_exists( auth::class, 'verifyMfaSecret' ) );
	}

	public function testVerifyMfaCodeMethodExists(): void {
		$this->assertTrue( method_exists( auth::class, 'verifyMfaCode' ) );
	}

	public function testOutMethodExists(): void {
		$this->assertTrue( method_exists( auth::class, 'out' ) );
	}

	public function testLifecycleHooksReturnVoid(): void {
		auth::_before();
		auth::_after();
		$reflection = new \ReflectionClass( auth::class );
		$this->assertSame( 'void', (string) $reflection->getMethod( '_before' )->getReturnType() );
		$this->assertSame( 'void', (string) $reflection->getMethod( '_after' )->getReturnType() );
	}

}

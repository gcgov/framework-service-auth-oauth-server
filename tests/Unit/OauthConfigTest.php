<?php

declare(strict_types=1);

namespace gcgov\framework\services\authoauth\tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use gcgov\framework\services\authoauth\oauthConfig;

#[CoversClass(oauthConfig::class)]
final class OauthConfigTest extends TestCase {

	protected function setUp(): void {
		$this->resetSingleton();
	}

	protected function tearDown(): void {
		$this->resetSingleton();
	}

	public function testGetInstanceReturnsSingleton(): void {
		$a = oauthConfig::getInstance();
		$b = oauthConfig::getInstance();
		$this->assertSame( $a, $b );
	}

	public function testNewUsersBlockedByDefault(): void {
		$config = oauthConfig::getInstance();
		$this->assertTrue( $config->isBlockNewUsers() );
		$this->assertSame( [], $config->getDefaultNewUserRoles() );
	}

	public function testSetBlockNewUsersFalseStoresRoles(): void {
		$config = oauthConfig::getInstance();
		$config->setBlockNewUsers( false, [ 'Role.A', 'Role.B' ] );
		$this->assertFalse( $config->isBlockNewUsers() );
		$this->assertSame( [ 'Role.A', 'Role.B' ], $config->getDefaultNewUserRoles() );
	}

	public function testSetBlockNewUsersTrueIgnoresRolesArgument(): void {
		$config = oauthConfig::getInstance();
		$config->setBlockNewUsers( true, [ 'Role.X' ] );
		$this->assertTrue( $config->isBlockNewUsers() );
		$this->assertSame( [], $config->getDefaultNewUserRoles() );
	}

	public function testAuthorizeUrlParametersDefaultEmpty(): void {
		$config = oauthConfig::getInstance();
		$this->assertSame( [], $config->getAuthorizeUrlParameters() );
	}

	public function testSetAuthorizeUrlParametersStoresValues(): void {
		$config = oauthConfig::getInstance();
		$params = [ 'response_type' => 'code', 'scope' => 'openid email' ];
		$config->setAuthorizeUrlParameters( $params );
		$this->assertSame( $params, $config->getAuthorizeUrlParameters() );
	}

	public function testConstructorIsPrivate(): void {
		$reflection = new \ReflectionMethod( oauthConfig::class, '__construct' );
		$this->assertTrue( $reflection->isPrivate() );
	}

	public function testCloneIsFinal(): void {
		$reflection = new \ReflectionMethod( oauthConfig::class, '__clone' );
		$this->assertTrue( $reflection->isFinal() );
	}

	public function testSleepReturnsEmptyArray(): void {
		$this->assertSame( [], oauthConfig::getInstance()->__sleep() );
	}

	public function testWakeupIsFinal(): void {
		$reflection = new \ReflectionMethod( oauthConfig::class, '__wakeup' );
		$this->assertTrue( $reflection->isFinal() );
	}

	public function testGetInstanceIsFinalAndStatic(): void {
		$reflection = new \ReflectionMethod( oauthConfig::class, 'getInstance' );
		$this->assertTrue( $reflection->isFinal() );
		$this->assertTrue( $reflection->isStatic() );
	}

	private function resetSingleton(): void {
		$prop = new \ReflectionProperty( oauthConfig::class, 'instance' );
		if ( $prop->isInitialized() ) {
			$prop->setValue( null, ( new \ReflectionClass( oauthConfig::class ) )->newInstanceWithoutConstructor() );
		}
	}

}

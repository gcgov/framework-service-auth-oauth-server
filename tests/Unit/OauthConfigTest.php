<?php

declare(strict_types=1);

namespace gcgov\framework\services\authoauth\tests\Unit;

use PHPUnit\Framework\TestCase;
use gcgov\framework\services\authoauth\oauthConfig;

final class OauthConfigTest extends TestCase {

	public function testGetInstanceReturnsSingleton(): void {
		$a = oauthConfig::getInstance();
		$b = oauthConfig::getInstance();
		$this->assertSame( $a, $b );
	}

	public function testCloneIsPrevented(): void {
		$reflection = new \ReflectionMethod( oauthConfig::class, '__clone' );
		$this->assertTrue( $reflection->isFinal() );
	}

	public function testSleepReturnsEmptyArray(): void {
		$this->assertSame( [], oauthConfig::getInstance()->__sleep() );
	}

}

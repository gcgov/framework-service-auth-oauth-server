<?php

namespace gcgov\framework\services\authoauth\models;

/**
 * @OA\Schema()
 */
class verifyMfaCodeRequest extends \andrewsauder\jsonDeserialize\jsonDeserialize {

	/** @OA\Property() */
	public string $code = '';


	public function __construct( string $code = '' ) {
		$this->code = $code;
	}

}

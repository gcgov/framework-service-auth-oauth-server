<?php

namespace gcgov\framework\services\authoauth\models;

/**
 * @OA\Schema()
 */
class requireMfaResponse extends stdAuthResponse {

	/** @OA\Property() */
	public bool $mfaRequired = true;

	/** @OA\Property() */
	public bool $mfaConfigured = true;


	public function __construct( ?\Lcobucci\JWT\Token\Plain $accessToken = null, \gcgov\framework\interfaces\auth\user $user=null ) {
		parent::__construct( $accessToken );
		if($user!==null) {
			$this->mfaRequired   = $user->mfaRequired;
			$this->mfaConfigured = $user->mfaConfigured;
		}
	}

}

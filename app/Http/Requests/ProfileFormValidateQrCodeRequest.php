<?php

namespace FireflyIII\Http\Requests;

use Auth;

/**
 * Class ProfileFormValidateQrCodeRequest
 *
 * @codeCoverageIgnore
 * @package FireflyIII\Http\Requests
 */
class ProfileFormValidateQrCodeRequest extends ProfileFormRequestBase
{    

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'code'                => 'required|digits:6',
        ];
    }

    /**
	 * Get the URL to redirect to on a validation error.
	 *
	 * @return string
	 */
	protected function getRedirectUrl()
	{
		$url = $this->redirector->getUrlGenerator();
		
		return $url->current();
	}
}

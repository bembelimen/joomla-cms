<?php

/**
 * Joomla! Content Management System
 *
 * @copyright  (C) 2023 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Joomla\CMS\Mail;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Registry\Registry;
use PHPMailer\PHPMailer\OAuthTokenProvider;
use Joomla\OAuth2\Client;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * OAuth2 token provider wrapper class for phpmailer.
 * Provides base64 encoded OAuth2 auth strings for SMTP authentication.
 *
 * @since  __DEPLOY_VERSION__
 */
abstract class MailerOAuthTokenProvider implements OAuthTokenProvider
{
    /**
     * An instance of the League OAuth Client Provider.
     *
     * @var AbstractProvider
     */
    protected $provider;

    /**
     * The current OAuth access token.
     *
     * @var AccessToken
     */
    protected $oauthToken;

    /**
     * The user's email address, usually used as the login ID
     * and also the from address when sending email.
     *
     * @var string
     */
    protected $oauthUserEmail = '';

    /**
     * The client secret, generated in the app definition of the service you're connecting to.
     *
     * @var string
     */
    protected $oauthClientSecret = '';

    /**
     * The client ID, generated in the app definition of the service you're connecting to.
     *
     * @var string
     */
    protected $oauthClientId = '';

    /**
     * The refresh token, used to obtain new AccessTokens.
     *
     * @var string
     */
    protected $oauthRefreshToken = '';

    /**
     * OAuth constructor.
     *
     * @param array $options Associative array containing
     *                       `provider`, `userName`, `clientSecret`, `clientId` and `refreshToken` elements
     */
    public function __construct($options)
    {
        $this->oauthUserEmail = $options['userName'];
        $this->oauthClientSecret = $options['clientSecret'];
        $this->oauthClientId = $options['clientId'];
        $this->oauthRefreshToken = $options['refreshToken'];

        // $this->provider = new Client(
        //     [
        //         'redirectUri' => 'https://example.com/redirect',
        //         'clientId' => $this->oauthClientId,
        //         'clientSecret' => $this->oauthClientSecret,
        //         'authurl' => 'https://example.com/auth',
        //         'tokenurl' => 'https://example.com/token',
        //     ],
        //     null, Factory::getApplication()->getInput()
        // );
    }

    /**
     * Get a new RefreshToken.
     *
     * @return RefreshToken
     */
    protected function getGrant()
    {
        // return new RefreshToken();
        return;
    }

    /**
     * Get a new AccessToken.
     *
     * @return AccessToken
     */
    protected function getToken()
    {
        // return $this->provider->getAccessToken(
        //     $this->getGrant(),
        //     ['refresh_token' => $this->oauthRefreshToken]
        // );
        return;
    }


    /**
     * Generate a base64-encoded OAuth token ensuring that the access token has not expired.
     * The string to be base 64 encoded should be in the form:
     * "user=<user_email_address>\001auth=Bearer <access_token>\001\001"
     *
     * @return string
     */
    public function getOauth64() {

        //Get a new token if it's not available or has expired
        // if (null === $this->oauthToken || $this->oauthToken->hasExpired()) {
        //     $this->oauthToken = $this->getToken();
        // }

        // return base64_encode(
        //     'user=' .
        //     $this->oauthUserEmail .
        //     "\001auth=Bearer " .
        //     $this->oauthToken .
        //     "\001\001"
        // );

        return '';
    }

}

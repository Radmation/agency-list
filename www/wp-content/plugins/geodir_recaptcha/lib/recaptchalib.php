<?php
/**
 * This is a PHP library that handles calling reCAPTCHA.
 *
 * @copyright Copyright (c) 2015, Google Inc.
 * @link      https://www.google.com/recaptcha
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace ReCaptchalib\ReCaptcha;


/**
 * reCAPTCHA client.
 */
class ReCaptcha
{

    /**
     * Version of this client library.
     * @const string
     */
    const VERSION = 'php_1.2.1';

    /**
     * URL for reCAPTCHA sitevrerify API
     * @const string
     */
    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /**
     * URL for reCAPTCHA signup page
     * @const string
     */
    const SIGNUP_URL = 'https://www.google.com/recaptcha/admin';

    /**
     * Invalid JSON received
     * @const string
     */
    const E_INVALID_JSON = 'invalid-json';

    /**
     * Could not connect to service
     * @const string
     */
    const E_CONNECTION_FAILED = 'connection-failed';

    /**
     * Did not receive a 200 from the service
     * @const string
     */
    const E_BAD_RESPONSE = 'bad-response';

    /**
     * Not a success, but no error codes received!
     * @const string
     */
    const E_UNKNOWN_ERROR = 'unknown-error';

    /**
     * ReCAPTCHA response not provided
     * @const string
     */
    const E_MISSING_INPUT_RESPONSE = 'missing-input-response';

    /**
     * Expected hostname did not match
     * @const string
     */
    const E_HOSTNAME_MISMATCH = 'hostname-mismatch';

    /**
     * Expected APK package name did not match
     * @const string
     */
    const E_APK_PACKAGE_NAME_MISMATCH = 'apk_package_name-mismatch';

    /**
     * Expected action did not match
     * @const string
     */
    const E_ACTION_MISMATCH = 'action-mismatch';

    /**
     * Score threshold not met
     * @const string
     */
    const E_SCORE_THRESHOLD_NOT_MET = 'score-threshold-not-met';

    /**
     * Challenge timeout
     * @const string
     */
    const E_CHALLENGE_TIMEOUT = 'challenge-timeout';

    /**
     * Shared secret for the site.
     * @var string
     */
    private $secret;

    /**
     * Create a configured instance to use the reCAPTCHA service.
     *
     * @param string $secret The shared key between your site and reCAPTCHA.
     * @throws \RuntimeException if $secret is invalid
     */
    public function __construct($secret)
    {
        if (empty($secret) || !is_string($secret)) {
            die("To use reCAPTCHA you must get an API key from <a href='"
                . self::SIGNUP_URL . "'>" . self::SIGNUP_URL . "</a>");
        }

        $this->secret = $secret;
    }

    /**
     * Calls the reCAPTCHA siteverify API to verify whether the user passes
     * CAPTCHA test and additionally runs any specified additional checks
     *
     * @param string $response The user response token provided by reCAPTCHA, verifying the user on your site.
     * @param string $remoteIp The end user's IP address.
     * @return Response Response from the service.
     */
    public function verifyResponse($remoteIp, $response)
    {
        // Discard empty solution submissions
        if (empty($response)) {
            return new Response(false, array(self::E_MISSING_INPUT_RESPONSE));
        }

        $params = array( 
            'secret'    => $this->secret, 
            'response'  => $response,
            'remoteip'  => $remoteIp,
            'version'   => self::VERSION,
        );

        $args = array(
            'url'       => self::SITE_VERIFY_URL,
            'method'    => 'POST',
            'headers'   => array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            ),
            'timeout'   => 10,
            'sslverify' => apply_filters('geodir_use_sslverify', true),
            'body'      => http_build_query($params, '', '&'),
        );

        $response = wp_remote_request(self::SITE_VERIFY_URL, $args);
        //var_dump(wp_remote_retrieve_body($response)); exit;
        //In case WordPress returned an error, abort ASAP
        if ($response instanceof WP_Error) {
            return new Response(false, array($response->get_error_message()));
        }
        
        $rawResponse = wp_remote_retrieve_body($response);
        $initialResponse = Response::fromJson($rawResponse);
        $validationErrors = array();

        if (isset($this->hostname) && strcasecmp($this->hostname, $initialResponse->getHostname()) !== 0) {
            $validationErrors[] = self::E_HOSTNAME_MISMATCH;
        }

        if (isset($this->apkPackageName) && strcasecmp($this->apkPackageName, $initialResponse->getApkPackageName()) !== 0) {
            $validationErrors[] = self::E_APK_PACKAGE_NAME_MISMATCH;
        }

        if (isset($this->action) && strcasecmp($this->action, $initialResponse->getAction()) !== 0) {
            $validationErrors[] = self::E_ACTION_MISMATCH;
        }

        if (isset($this->threshold) && $this->threshold > $initialResponse->getScore()) {
            $validationErrors[] = self::E_SCORE_THRESHOLD_NOT_MET;
        }

        if (isset($this->timeoutSeconds)) {
            $challengeTs = strtotime($initialResponse->getChallengeTs());
            if ($challengeTs > 0 && time() - $challengeTs > $this->timeoutSeconds) {
                $validationErrors[] = self::E_CHALLENGE_TIMEOUT;
            }
        }

        if (empty($validationErrors)) {
            return $initialResponse;
        }

        return new Response(
            false,
            array_merge($initialResponse->getErrorCodes(), $validationErrors),
            $initialResponse->getHostname(),
            $initialResponse->getChallengeTs(),
            $initialResponse->getApkPackageName(),
            $initialResponse->getScore(),
            $initialResponse->getAction()
        );
    }

    /**
     * Provide a hostname to match against in verify()
     * This should be without a protocol or trailing slash, e.g. www.google.com
     *
     * @param string $hostname Expected hostname
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedHostname($hostname)
    {
        $this->hostname = $hostname;
        return $this;
    }

    /**
     * Provide an APK package name to match against in verify()
     *
     * @param string $apkPackageName Expected APK package name
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedApkPackageName($apkPackageName)
    {
        $this->apkPackageName = $apkPackageName;
        return $this;
    }

    /**
     * Provide an action to match against in verify()
     * This should be set per page.
     *
     * @param string $action Expected action
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setExpectedAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * Provide a threshold to meet or exceed in verify()
     * Threshold should be a float between 0 and 1 which will be tested as response >= threshold.
     *
     * @param float $threshold Expected threshold
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setScoreThreshold($threshold)
    {
        $this->threshold = floatval($threshold);
        return $this;
    }

    /**
     * Provide a timeout in seconds to test against the challenge timestamp in verify()
     *
     * @param int $timeoutSeconds Expected hostname
     * @return ReCaptcha Current instance for fluent interface
     */
    public function setChallengeTimeout($timeoutSeconds)
    {
        $this->timeoutSeconds = $timeoutSeconds;
        return $this;
    }

}

/**
 * The response returned from the service.
 */
class Response
{
    /**
     * Success or failure.
     * @var boolean
     */
    private $success = false;
    /**
     * Error code strings.
     * @var array
     */
    private $errorCodes = array();
    /**
     * The hostname of the site where the reCAPTCHA was solved.
     * @var string
     */
    private $hostname;
    /**
     * Timestamp of the challenge load (ISO format yyyy-MM-dd'T'HH:mm:ssZZ)
     * @var string
     */
    private $challengeTs;
    /**
     * APK package name
     * @var string
     */
    private $apkPackageName;
    /**
     * Score assigned to the request
     * @var float
     */
    private $score;
    /**
     * Action as specified by the page
     * @var string
     */
    private $action;
    /**
     * Build the response from the expected JSON returned by the service.
     *
     * @param string $json
     * @return \ReCaptcha\Response
     */
    public static function fromJson($json)
    {
        $responseData = json_decode($json, true);
        if (!$responseData) {
            return new Response(false, array(ReCaptcha::E_INVALID_JSON));
        }
        $hostname = isset($responseData['hostname']) ? $responseData['hostname'] : null;
        $challengeTs = isset($responseData['challenge_ts']) ? $responseData['challenge_ts'] : null;
        $apkPackageName = isset($responseData['apk_package_name']) ? $responseData['apk_package_name'] : null;
        $score = isset($responseData['score']) ? floatval($responseData['score']) : null;
        $action = isset($responseData['action']) ? $responseData['action'] : null;
        if (isset($responseData['success']) && $responseData['success'] == true) {
            return new Response(true, array(), $hostname, $challengeTs, $apkPackageName, $score, $action);
        }
        if (isset($responseData['error-codes']) && is_array($responseData['error-codes'])) {
            return new Response(false, $responseData['error-codes'], $hostname, $challengeTs, $apkPackageName, $score, $action);
        }
        return new Response(false, array(ReCaptcha::E_UNKNOWN_ERROR), $hostname, $challengeTs, $apkPackageName, $score, $action);
    }
    /**
     * Constructor.
     *
     * @param boolean $success
     * @param string $hostname
     * @param string $challengeTs
     * @param string $apkPackageName
     * @param float $score
     * @param string $action
     * @param array $errorCodes
     */
    public function __construct($success, array $errorCodes = array(), $hostname = null, $challengeTs = null, $apkPackageName = null, $score = null, $action = null)
    {
        $this->success = $success;
        $this->hostname = $hostname;
        $this->challengeTs = $challengeTs;
        $this->apkPackageName = $apkPackageName;
        $this->score = $score;
        $this->action = $action;
        $this->errorCodes = $errorCodes;
    }
    /**
     * Is success?
     *
     * @return boolean
     */
    public function isSuccess()
    {
        return $this->success;
    }
    /**
     * Get error codes.
     *
     * @return array
     */
    public function getErrorCodes()
    {
        return $this->errorCodes;
    }
    /**
     * Get hostname.
     *
     * @return string
     */
    public function getHostname()
    {
        return $this->hostname;
    }
    /**
     * Get challenge timestamp
     *
     * @return string
     */
    public function getChallengeTs()
    {
        return $this->challengeTs;
    }
    /**
     * Get APK package name
     *
     * @return string
     */
    public function getApkPackageName()
    {
        return $this->apkPackageName;
    }
    /**
     * Get score
     *
     * @return float
     */
    public function getScore()
    {
        return $this->score;
    }
    /**
     * Get action
     *
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }
    public function toArray()
    {
        return array(
            'success' => $this->isSuccess(),
            'hostname' => $this->getHostname(),
            'challenge_ts' => $this->getChallengeTs(),
            'apk_package_name' => $this->getApkPackageName(),
            'score' => $this->getScore(),
            'action' => $this->getAction(),
            'error-codes' => $this->getErrorCodes(),
        );
    }
}
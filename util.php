<?php

use \Tsugi\Core\Keyset;
use \Tsugi\Util\LTI13;
use \Tsugi\Util\Net;
use \Tsugi\Util\U;

/**
 * OAuth scopes for Sakai SAT token requests.
 *
 * v0: one standard IMS scope — enough to exercise client_assertion signing,
 * token issue, and bearer round-trip on /api/lti/bearer-probe. Add more via
 * sakai_token_scope once Sakai grants additional scopes for real webapi routes.
 */
function sakai_token_scopes($launch) {
    $override = $launch->settingsCascade('sakai_token_scope', null);
    if ( is_string($override) && U::strlen(trim($override)) > 0 ) {
        return preg_split('/\s+/', trim($override));
    }

    return array(
        "https://purl.imsglobal.org/spec/lti-ags/scope/lineitem",
    );
}

/**
 * Load LTI 1.3 token-request fields from the Tsugi session (same checks as Context).
 *
 * @return string Empty on success, otherwise a space-separated list of missing fields.
 */
function sakai_load_lti13_data($launch, &$lti13_token_url, &$privkey, &$kid,
    &$lti13_token_audience, &$issuer_client, &$deployment_id) {

    $success = Keyset::getSigning($privkey, $kid);

    $lti13_token_url = $launch->ltiParameter('lti13_token_url');
    $issuer_client = $launch->ltiParameter('issuer_client');
    $lti13_token_audience = $launch->ltiParameter('lti13_token_audience');
    $deployment_id = $launch->ltiParameter('deployment_id');

    $missing = '';
    if ( empty($issuer_client) ) $missing .= ' issuer_client';
    if ( empty($privkey) ) $missing .= ' private_key';
    if ( empty($kid) ) $missing .= ' public_key_kid';
    if ( empty($lti13_token_url) ) $missing .= ' token_url';
    return trim($missing);
}

/**
 * Sakai webapi context path (/api) from issuer URL or optional key setting.
 */
function sakai_api_root($launch) {
    $override = $launch->settingsCascade('sakai_api_root', null);
    if ( is_string($override) && U::strlen(trim($override)) > 0 ) {
        return rtrim(trim($override), '/');
    }

    $iss = $launch->ltiParameter('issuer_key');
    if ( ! is_string($iss) || U::strlen(trim($iss)) < 1 ) {
        return false;
    }
    return rtrim(trim($iss), '/') . '/api';
}

function sakai_bearer_probe_url($launch) {
    $root = sakai_api_root($launch);
    if ( ! $root ) return false;
    return $root . '/lti/bearer-probe';
}

/**
 * Obtain a Sakai Access Token (SAT) and call GET /api/lti/bearer-probe.
 *
 * @return array Keys: ok, missing, token_url, probe_url, token_data, access_token,
 *     probe_http_code, probe_body, probe_json, debug_log
 */
function sakai_get_token_and_probe($launch) {
    $debug_log = array();
    $result = array(
        'ok' => false,
        'missing' => '',
        'token_url' => '',
        'probe_url' => '',
        'token_data' => false,
        'access_token' => false,
        'probe_http_code' => false,
        'probe_body' => false,
        'probe_json' => false,
        'debug_log' => &$debug_log,
    );

    $missing = sakai_load_lti13_data($launch, $lti13_token_url, $privkey, $kid,
        $lti13_token_audience, $issuer_client, $deployment_id);
    $result['missing'] = $missing;
    if ( U::strlen($missing) > 0 ) {
        $debug_log[] = 'Missing LTI 1.3 session data:' . $missing;
        return $result;
    }

    $probe_url = sakai_bearer_probe_url($launch);
    $result['token_url'] = $lti13_token_url;
    $result['probe_url'] = $probe_url;
    if ( ! $probe_url ) {
        $debug_log[] = 'Could not determine Sakai /api base from issuer_key';
        $result['missing'] = 'issuer_key';
        return $result;
    }

    $scopes = sakai_token_scopes($launch);
    $debug_log[] = 'Requested scopes: ' . (is_array($scopes) ? implode(' ', $scopes) : $scopes);

    $token_data = LTI13::get_access_token($scopes, $issuer_client, $lti13_token_url,
        $privkey, $kid, $lti13_token_audience, $deployment_id, $debug_log);
    $result['token_data'] = $token_data;

    $access_token = LTI13::extract_access_token($token_data, $debug_log);
    $result['access_token'] = $access_token;
    if ( ! $access_token ) {
        $debug_log[] = 'Token request did not return access_token';
        return $result;
    }

    $header = "Authorization: Bearer " . $access_token . "\n"
        . "Accept: application/json";
    $debug_log[] = 'GET ' . $probe_url;
    $debug_log[] = $header;

    $probe_body = Net::doGet($probe_url, $header);
    $result['probe_http_code'] = Net::getLastHttpResponse();
    $result['probe_body'] = $probe_body;

    if ( is_string($probe_body) && U::strlen($probe_body) > 0 ) {
        $json = json_decode($probe_body);
        if ( $json !== null ) {
            $result['probe_json'] = $json;
        }
    }

    $code = $result['probe_http_code'];
    $result['ok'] = ($code == 200 && is_object($result['probe_json'])
        && isset($result['probe_json']->ok) && $result['probe_json']->ok);

    return $result;
}

function sakai_print_debug_log($div, $debug_log) {
    echo('<div id="'.$div.'"><pre>'."\n");
    if ( is_array($debug_log) && count($debug_log) > 0 ) {
        echo(htmlentities(\Tsugi\UI\Output::safe_print_r($debug_log), ENT_SUBSTITUTE));
    }
    echo("\n</pre></div>\n");
}

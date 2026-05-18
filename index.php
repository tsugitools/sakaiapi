<?php
// https://github.com/tsugicontrib/sakaiapi
require_once "../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\UI\Output;

require_once "util.php";

$LTI = LTIX::requireData();
require_once("nav.php");

$do_probe = U::get($_GET, 'probe') == '1';
$probe_result = false;
if ( $do_probe ) {
    $probe_result = sakai_get_token_and_probe($LTI);
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->welcomeUserCourse();

$issuer = htmlentities($LTI->ltiParameter('issuer_key'));
$token_url = htmlentities($LTI->ltiParameter('lti13_token_url'));
$api_root = sakai_api_root($LTI);
$probe_url = sakai_bearer_probe_url($LTI);
$scopes = sakai_token_scopes($LTI);
$scope_display = is_array($scopes) ? implode("\n", $scopes) : $scopes;

?>
<h1>Sakai API bearer test</h1>
<p>
  This tool obtains a Sakai Access Token (SAT) via the LTI Advantage client-credentials
  flow, then calls <code>GET /api/lti/bearer-probe</code> with
  <code>Authorization: Bearer</code>.
</p>

<dl class="dl-horizontal">
  <dt>Issuer</dt><dd><?= $issuer ?></dd>
  <dt>Token URL</dt><dd><code><?= $token_url ?></code></dd>
  <dt>API root</dt><dd><code><?= htmlentities($api_root ? $api_root : '(unknown)') ?></code></dd>
  <dt>Probe URL</dt><dd><code><?= htmlentities($probe_url ? $probe_url : '(unknown)') ?></code></dd>
</dl>

<h2>Scopes requested</h2>
<pre><?= htmlentities($scope_display) ?></pre>
<p class="text-muted">
  v0 uses one LTI scope to validate SAT signing and the probe endpoint.
  Token audience follows normal LTI rules from your issuer registration.
  Overrides: <code>sakai_token_scope</code> (space-separated),
  <code>sakai_api_root</code> (e.g. <code>https://localhost:8080/api</code>).
</p>

<p>
  <a class="btn btn-primary" href="index.php?probe=1">Get token &amp; probe</a>
</p>

<?php if ( $do_probe ) { ?>
<div id="probe-results">
  <h2>Results</h2>
<?php if ( $probe_result && $probe_result['ok'] ) { ?>
  <div class="alert alert-success">Probe succeeded (HTTP <?= (int) $probe_result['probe_http_code'] ?>).</div>
<?php } else { ?>
  <div class="alert alert-warning">Probe did not report success.
<?php if ( $probe_result && $probe_result['probe_http_code'] ) { ?>
  HTTP <?= (int) $probe_result['probe_http_code'] ?>.
<?php } ?>
<?php if ( $probe_result && U::strlen($probe_result['missing']) > 0 ) { ?>
  Missing: <?= htmlentities($probe_result['missing']) ?>.
<?php } ?>
  </div>
<?php } ?>

<?php if ( $probe_result && is_array($probe_result['token_data']) ) { ?>
  <h3>Token response</h3>
  <pre><?= htmlentities(Output::safe_print_r($probe_result['token_data'])) ?></pre>
<?php } ?>

<?php if ( $probe_result && $probe_result['access_token'] ) { ?>
  <h3>Access token (SAT)</h3>
  <pre><?= htmlentities($probe_result['access_token']) ?></pre>
<?php } ?>

<?php if ( $probe_result && $probe_result['probe_body'] !== false ) { ?>
  <h3>Probe response</h3>
  <pre><?= htmlentities($probe_result['probe_body']) ?></pre>
<?php } ?>

<?php sakai_print_debug_log('debug_log', $probe_result ? $probe_result['debug_log'] : array()); ?>
</div>
<?php } ?>

<?php
$OUTPUT->footer();

<?php
// https://github.com/tsugicontrib/sakaiapi
require_once "../config.php";

use \Tsugi\Core\LTIX;
use \Tsugi\Util\U;
use \Tsugi\UI\Output;

require_once "util.php";

$LTI = LTIX::requireData();
require_once("nav.php");

$probe_preset = U::get($_GET, 'probe');
if ( $probe_preset === '1' ) {
    $probe_preset = 'lti';
}
$do_probe = ($probe_preset === 'lti' || $probe_preset === 'sakai');
$probe_result = false;
if ( $do_probe ) {
    $probe_result = sakai_get_token_and_probe($LTI, $probe_preset);
}

$OUTPUT->header();
$OUTPUT->bodyStart();
$OUTPUT->topNav($menu);
$OUTPUT->welcomeUserCourse();

$issuer = htmlentities($LTI->ltiParameter('issuer_key'));
$token_url = htmlentities($LTI->ltiParameter('lti13_token_url'));
$api_root = sakai_api_root($LTI);
$probe_url = sakai_bearer_probe_url($LTI);

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

<h2>Probe tests</h2>
<p class="text-muted">
  Token audience follows normal LTI rules from your issuer registration.
  Optional overrides: <code>sakai_token_scope</code>, <code>sakai_api_root</code>.
</p>
<p>
  <a class="btn btn-primary" href="index.php?probe=lti">LTI scope &amp; probe</a>
  <a class="btn btn-default" href="index.php?probe=sakai">Sakai scope &amp; probe</a>
</p>
<ul class="text-muted">
  <li><strong>LTI scope:</strong> <code>https://purl.imsglobal.org/spec/lti-ags/scope/lineitem</code> (working)</li>
  <li><strong>Sakai scope:</strong> <code>sakai.lti.api.content.read</code> (expected to fail until granted in Sakai)</li>
</ul>

<?php if ( $do_probe ) { ?>
<div id="probe-results">
  <h2>Results — <?= htmlentities($probe_result ? $probe_result['preset_label'] : '') ?></h2>
<?php if ( $probe_result && is_array($probe_result['scopes']) ) { ?>
  <p><strong>Scopes requested:</strong></p>
  <pre><?= htmlentities(implode("\n", $probe_result['scopes'])) ?></pre>
<?php } ?>
<?php if ( $probe_result && $probe_result['ok'] ) { ?>
  <div class="alert alert-success">Probe succeeded (HTTP <?= (int) $probe_result['probe_http_code'] ?>).</div>
<?php } else { ?>
  <div class="alert alert-warning">Probe did not report success.
<?php if ( $probe_result && $probe_result['probe_http_code'] ) { ?>
  HTTP <?= (int) $probe_result['probe_http_code'] ?>.
<?php } ?>
<?php if ( $probe_result && ! $probe_result['access_token'] ) { ?>
  Token request failed or returned no <code>access_token</code> (expected for Sakai-only scope until configured).
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

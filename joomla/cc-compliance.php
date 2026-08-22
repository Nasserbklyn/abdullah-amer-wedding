<?php
/**
 * Cuse Clouds Cannabis — NY OCM Part 129 compliance layer for Joomla.
 *
 * Renders, on EVERY front-end page of the Joomla site (Helix Framework /
 * Flex template / SP Page Builder alike):
 *   1. a 21+ age gate that blocks the page until the visitor confirms;
 *   2. a bright-yellow (#FFFF00) conspicuous consumer-warning band carrying
 *      one of the four required warnings, rotated between page loads;
 *   3. a compliance footer with the OCM license number and the NYS HOPEline.
 *
 * INSTALL (see joomla/INSTALL.md):
 *   Upload this file to the site root (next to configuration.php), then add
 *   ONE line to the active template's index.php immediately after the opening
 *   <body ...> tag:
 *
 *       <?php include_once JPATH_BASE . '/cc-compliance.php'; ?>
 *
 * Everything is inline and self-contained: no database row, no extra HTTP
 * request, no dependency on Bootstrap, jQuery, or the template's CSS. All
 * selectors are namespaced `cc-` so nothing collides with Helix or SPPB.
 *
 * To disable: remove that one include line. Nothing else is modified.
 */

defined('_JEXEC') or die;

// Administrator and raw/JSON output must never get the overlay.
$ccApp = class_exists('\\Joomla\\CMS\\Factory') ? \Joomla\CMS\Factory::getApplication() : null;
if ($ccApp) {
    if (method_exists($ccApp, 'isClient') && !$ccApp->isClient('site')) {
        return;
    }
    $ccFormat = $ccApp->input->getCmd('format', 'html');
    $ccTmpl   = $ccApp->input->getCmd('tmpl', '');
    if ($ccFormat !== 'html' || in_array($ccTmpl, array('component', 'raw'), true)) {
        return;
    }
}

$ccLicense  = 'OCM-RETL-26-000487';
$ccBusiness = 'On The Bus Inc. d/b/a Cuse Clouds';
$ccAddress  = '900 E Fayette St, Syracuse, NY 13210';
$ccPhone    = '(315) 214-4017';
$ccPhoneRaw = '+13152144017';
$ccEmail    = 'cs@cuseclouds.com';
$ccGeneral  = 'For use only by persons 21 years of age and older. Keep out of reach of children and pets. '
            . 'If someone accidentally consumes cannabis, contact the Poison Center. Consume responsibly.';
?>
<style id="cc-compliance-css">
/* ---- Cuse Clouds OCM compliance layer -------------------------------- */
.cc-band{
  background:#FFFF00 !important;color:#000 !important;
  border-bottom:3px solid #000 !important;
  padding:.85rem 1rem !important;text-align:center !important;
  position:relative !important;z-index:1200 !important;
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif !important;
}
.cc-band p{
  margin:0 auto !important;max-width:70rem !important;
  font-size:.95rem !important;line-height:1.5 !important;font-weight:700 !important;
  color:#000 !important;
}
.cc-band .cc-rot{display:block !important;margin-top:.35rem !important;font-weight:800 !important}

html.cc-locked,html.cc-locked body{overflow:hidden !important;height:100% !important}

#cc-gate{
  position:fixed !important;inset:0 !important;z-index:2147483000 !important;
  background-color:#05080d !important;
  background-image:linear-gradient(165deg,#060a11,#05080d 60%,#040609) !important;
  display:flex !important;align-items:center !important;justify-content:center !important;
  padding:1.2rem !important;overflow-y:auto !important;
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif !important;
}
html.cc-verified #cc-gate{display:none !important}
#cc-gate .cc-card{
  width:min(540px,94vw);margin:auto;text-align:center;
  background:linear-gradient(170deg,#0e1a2e,#0b1526);
  border:1px solid rgba(94,193,255,.25);border-radius:22px;
  padding:clamp(1.4rem,4vw,2.2rem);color:#eef4fa;box-shadow:0 30px 70px rgba(0,0,0,.55);
}
#cc-gate .cc-mark{
  font-style:italic;font-weight:800;letter-spacing:.02em;
  font-size:clamp(1.15rem,3.4vw,1.5rem);margin:0 0 1.1rem;color:#eef4fa;line-height:1.2;
}
#cc-gate .cc-mark span{color:#5ec1ff}
#cc-gate h2{font-size:1.3rem;margin:0 0 .45rem;color:#eef4fa;font-weight:700;line-height:1.3}
#cc-gate .cc-sub{color:#9db2c7;font-size:.95rem;margin:0;line-height:1.6}
#cc-gate .cc-lic{color:#9db2c7;font-size:.8rem;margin:.9rem 0 0;letter-spacing:.05em}
#cc-gate .cc-btns{display:flex;gap:.8rem;justify-content:center;flex-wrap:wrap;margin-top:1.3rem}
#cc-gate button{
  display:inline-flex;align-items:center;justify-content:center;
  padding:.82rem 1.7rem;border-radius:999px;font-weight:600;font-size:.95rem;
  border:1px solid transparent;cursor:pointer;font-family:inherit;line-height:1.2;
}
#cc-gate .cc-yes{background:linear-gradient(160deg,#5ec1ff,#2196f3 55%,#0b4e9c);color:#fff}
#cc-gate .cc-no{background:transparent;border-color:rgba(94,193,255,.4);color:#eef4fa}
#cc-gate button:focus-visible{outline:3px solid #5ec1ff;outline-offset:3px}
#cc-gate .cc-warn{
  background:#FFFF00;color:#000;border-radius:12px;padding:.8rem .9rem;margin-top:1.3rem;
  font-size:.8rem;font-weight:600;line-height:1.55;text-align:left;
}
#cc-gate .cc-help{color:#9db2c7;font-size:.78rem;margin:.9rem 0 0;line-height:1.6}
#cc-gate .cc-help a{color:#5ec1ff}

.cc-foot{
  background:#05080d;color:#9db2c7;border-top:1px solid rgba(94,193,255,.16);
  padding:1.8rem 1rem;text-align:center;position:relative;z-index:1100;
  font-family:system-ui,-apple-system,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  font-size:.85rem;line-height:1.7;
}
.cc-foot p{margin:.35rem auto;max-width:62rem;color:#9db2c7;font-size:.85rem;line-height:1.7}
.cc-foot a{color:#5ec1ff;text-decoration:none}
.cc-foot a:hover{text-decoration:underline}
.cc-foot .cc-foot-warn{
  background:#FFFF00;color:#000;font-weight:700;border-radius:10px;
  padding:.8rem 1rem;max-width:62rem;margin:0 auto 1.1rem;text-align:center;font-size:.85rem;
}
@media (max-width:600px){
  .cc-band p{font-size:.86rem !important}
  #cc-gate .cc-btns button{width:100%}
}
</style>

<div class="cc-band" role="region" aria-label="Cannabis consumer warning">
  <p>
    <?php echo $ccGeneral; ?>
    <span class="cc-rot" id="cc-rotating">Cannabis can be addictive.</span>
  </p>
</div>

<div id="cc-gate" role="dialog" aria-modal="true" aria-labelledby="cc-gate-title">
  <div class="cc-card">
    <p class="cc-mark">CUSE <span>CLOUDS</span> CANNABIS</p>
    <h2 id="cc-gate-title">Are you 21 years of age or older?</h2>
    <p class="cc-sub">You must be at least 21 years old to enter this website.</p>
    <p class="cc-lic">NYS OCM License # <?php echo $ccLicense; ?></p>
    <div class="cc-btns">
      <button type="button" class="cc-yes" id="cc-gate-yes">Yes, I am 21 or older</button>
      <button type="button" class="cc-no" id="cc-gate-no">No, I am under 21</button>
    </div>
    <p class="cc-warn"><?php echo $ccGeneral; ?></p>
    <p class="cc-help">
      Need help? NYS HOPEline &mdash; Call <a href="tel:+18778467369">1&#8209;877&#8209;8&#8209;HOPENY (1&#8209;877&#8209;846&#8209;7369)</a>
      &middot; Text HOPENY (467369) &middot;
      <a href="https://oasas.ny.gov/hopeline" target="_blank" rel="noopener">oasas.ny.gov/hopeline</a>
    </p>
  </div>
</div>

<script id="cc-compliance-js">
(function () {
  'use strict';

  var WARNINGS = [
    'Cannabis can be addictive.',
    'Cannabis can impair concentration and coordination. Do not operate a vehicle or machinery under the influence of cannabis.',
    'There may be health risks associated with consumption of this product.',
    'Cannabis is not recommended for use by persons who are pregnant or nursing.'
  ];

  var root = document.documentElement;

  /* ---- age gate ------------------------------------------------------ */
  var verified = false;
  try { verified = window.sessionStorage.getItem('cc_age_verified') === 'yes'; } catch (e) {}

  if (verified) {
    root.classList.add('cc-verified');
  } else {
    root.classList.add('cc-locked');
  }

  function unlock() {
    try { window.sessionStorage.setItem('cc_age_verified', 'yes'); } catch (e) {}
    root.classList.remove('cc-locked');
    root.classList.add('cc-verified');
  }

  var yes = document.getElementById('cc-gate-yes');
  var no  = document.getElementById('cc-gate-no');
  if (yes) {
    yes.addEventListener('click', unlock);
    if (!verified) { try { yes.focus(); } catch (e) {} }
  }
  if (no) {
    no.addEventListener('click', function () {
      window.location.replace('https://www.google.com');
    });
  }

  /* Keep focus inside the gate while it is open. */
  document.addEventListener('keydown', function (ev) {
    if (!root.classList.contains('cc-locked')) return;
    if (ev.key === 'Tab' && yes && no) {
      var active = document.activeElement;
      if (ev.shiftKey && active === yes) { ev.preventDefault(); no.focus(); }
      else if (!ev.shiftKey && active === no) { ev.preventDefault(); yes.focus(); }
      else if (active !== yes && active !== no) { ev.preventDefault(); yes.focus(); }
    }
  });

  /* ---- rotating required warning ------------------------------------- */
  var slot = document.getElementById('cc-rotating');
  if (slot) {
    var i = 0;
    try {
      i = (parseInt(window.localStorage.getItem('cc_warn_i'), 10) || 0) % WARNINGS.length;
      window.localStorage.setItem('cc_warn_i', String((i + 1) % WARNINGS.length));
    } catch (e) {
      i = Math.floor(Math.random() * WARNINGS.length);
    }
    slot.textContent = WARNINGS[i];

    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reduce) {
      window.setInterval(function () {
        i = (i + 1) % WARNINGS.length;
        slot.textContent = WARNINGS[i];
      }, 12000);
    }
  }

  /* ---- compliance footer --------------------------------------------- */
  function addFooter() {
    if (document.querySelector('.cc-foot')) return;
    var f = document.createElement('footer');
    f.className = 'cc-foot';
    f.innerHTML =
      '<p class="cc-foot-warn"><?php echo addslashes($ccGeneral); ?></p>' +
      '<p><strong style="color:#c9d3dc"><?php echo addslashes($ccBusiness); ?></strong> &middot; ' +
      'Licensed Adult-Use Cannabis Retail Dispensary &middot; NYS OCM License # <?php echo $ccLicense; ?></p>' +
      '<p><?php echo addslashes($ccAddress); ?> &middot; ' +
      '<a href="tel:<?php echo $ccPhoneRaw; ?>"><?php echo $ccPhone; ?></a> &middot; ' +
      '<a href="mailto:<?php echo $ccEmail; ?>"><?php echo $ccEmail; ?></a></p>' +
      '<p>Need help with cannabis or substance use? NYS HOPEline &mdash; ' +
      'Call <a href="tel:+18778467369">1&#8209;877&#8209;8&#8209;HOPENY (1&#8209;877&#8209;846&#8209;7369)</a> &middot; ' +
      'Text HOPENY (467369) &middot; ' +
      '<a href="https://oasas.ny.gov/hopeline" target="_blank" rel="noopener">oasas.ny.gov/hopeline</a></p>' +
      '<p>This website is intended only for persons 21 years of age or older.</p>';
    document.body.appendChild(f);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addFooter);
  } else {
    addFooter();
  }
})();
</script>

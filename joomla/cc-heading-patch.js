/**
 * Cuse Clouds — front-end heading patch.
 *
 * ⚠ THIS IS A COVER, NOT A FIX.
 *
 * The heading text lives in the SP Page Builder page layout in the database.
 * Editing it there is a four-click job in the builder and is the correct fix.
 * Two attempts at that save did not persist, so this rewrites the heading in
 * the DOM on load instead.
 *
 * What that means in practice:
 *   - Screen readers and anything reading the live DOM see the new wording.
 *   - The HTML that leaves the server still contains the old wording, so a
 *     raw-HTML fetch (curl, "view source", some crawlers) shows "OUR TEAM".
 *     Google executes JavaScript and will normally see the new text, but the
 *     first-pass HTML does not carry it.
 *
 * Delete this file and its <script> tag in templates/flex/index.php the moment
 * the heading is corrected in SP Page Builder.
 */

(function () {
  'use strict';

  var SECTION_ID  = 'section-id-1481572543';
  var FROM        = 'OUR TEAM';
  var TO          = 'Knowledgeable Staff';

  function patch() {
    // Scope to the one section, so nothing else on the page can be caught.
    var scope = document.getElementById(SECTION_ID);
    if (!scope) { return false; }

    var candidates = scope.querySelectorAll('h1, h2, h3, h4, h5, h6, .sppb-addon-title');

    for (var i = 0; i < candidates.length; i++) {
      var el = candidates[i];

      // Only touch a plain-text heading — never one wrapping other markup.
      if (el.children.length !== 0) { continue; }

      if (el.textContent.trim().toUpperCase() === FROM) {
        el.textContent = TO;
        return true;
      }
    }
    return false;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', patch);
  } else {
    patch();
  }
})();

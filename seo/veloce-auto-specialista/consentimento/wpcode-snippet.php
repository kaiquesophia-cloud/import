// Veloce — Google Consent Mode v2 ligado ao CookieAdmin. WPCode: tipo PHP, "Executar em todo lugar".
add_action( 'wp_head', function () { ?>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
(function () {
  function sim(v) { return v === true || v === "true"; }
  function ler() {
    try {
      var m = document.cookie.match(/(?:^|;\s*)cookieadmin_consent=([^;]+)/);
      return m ? JSON.parse(decodeURIComponent(m[1])) : null;
    } catch (e) { return null; }
  }
  function estado(c) {
    var a = false, k = false;
    if (c && sim(c.accept)) { a = k = true; }
    else if (c && !sim(c.reject)) { a = sim(c.analytics); k = sim(c.marketing); }
    var g = function (x) { return x ? "granted" : "denied"; };
    return { analytics_storage: g(a), ad_storage: g(k), ad_user_data: g(k), ad_personalization: g(k) };
  }
  gtag("consent", "default", Object.assign(estado(null), { wait_for_update: 500 }));
  gtag("set", "ads_data_redaction", true);
  gtag("set", "url_passthrough", true);
  var c = ler();
  if (c) gtag("consent", "update", estado(c));
  // O CookieAdmin chama esta função sempre que o visitante salva a escolha de cookies.
  window.cookieadmin_update_gcm = function () {
    gtag("consent", "update", estado(ler() || (window.cookieadmin_is_consent || {}).action));
  };
})();
</script>
<?php }, 1 );

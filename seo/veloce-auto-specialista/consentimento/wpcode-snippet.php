// Veloce — Google Consent Mode v2 ligado ao CookieAdmin. NO AR desde 20/11/2026 pelo Elementor Pro (Código personalizado, ID 630).
// Este arquivo é só reserva: usar no WPCode (tipo PHP, "Executar em todo lugar") apenas se o 630 for removido. Nunca os dois juntos.
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
  // Entra antes dos comandos do Google que já estiverem na fila (o Site Kit e o GTM imprimem antes deste código).
  function args() { return arguments; }
  var c = ler(), ini = [args("consent", "default", Object.assign(estado(null), { wait_for_update: 500 })),
    args("set", "ads_data_redaction", true), args("set", "url_passthrough", true)];
  if (c) ini.push(args("consent", "update", estado(c)));
  if (dataLayer.push === Array.prototype.push) { dataLayer.unshift.apply(dataLayer, ini); }
  else { ini.forEach(function (x) { dataLayer.push(x); }); }
  // O CookieAdmin chama esta função sempre que o visitante salva a escolha de cookies.
  window.cookieadmin_update_gcm = function () {
    gtag("consent", "update", estado(ler() || (window.cookieadmin_is_consent || {}).action));
  };
})();
</script>
<?php }, 1 );

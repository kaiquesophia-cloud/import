(function () {
  // Conversões do Google Ads (conta 716-122-9165), injetadas por `aplicar.py rastreio '<json>'`:
  // {whatsapp: "AW-.../...", telefone: "AW-.../...", formulario: "AW-.../..."}
  var ADS = window.AQ_ADS_CONV || {};

  function enviar(tipo, origem) {
    if (typeof window.gtag !== "function") return;
    window.gtag("event", tipo === "whatsapp" ? "clique_whatsapp" : "clique_telefone", {
      pagina: location.pathname,
      origem: origem
    });
    if (ADS[tipo]) window.gtag("event", "conversion", {send_to: ADS[tipo]});
  }

  // Formulário: só conta quando o Contact Form 7 confirma o envio (o GA4 já recebe pelo Site Kit)
  document.addEventListener("wpcf7mailsent", function () {
    if (typeof window.gtag === "function" && ADS.formulario) {
      window.gtag("event", "conversion", {send_to: ADS.formulario});
    }
  });

  document.addEventListener("click", function (e) {
    var alvo = e.target && e.target.closest && e.target.closest("a[href], #ht-ctc-chat");
    if (!alvo) return;
    if (alvo.id === "ht-ctc-chat") return enviar("whatsapp", "botao_flutuante");
    var href = alvo.getAttribute("href") || "";
    if (/wa\.me|api\.whatsapp\.com|whatsapp:/i.test(href)) enviar("whatsapp", "link");
    else if (/^tel:/i.test(href)) enviar("telefone", "link");
  }, true);
})();

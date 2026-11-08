(function () {
    "use strict";

    var anahtar = "wordnest-tema";
    var tema = "gunduz";
    try {
        tema = localStorage.getItem(anahtar) || "gunduz";
    } catch (e) {}

    function temayiUygula(yeniTema) {
        tema = yeniTema;
        if (tema === "gece") {
            document.documentElement.setAttribute("data-wordnest-theme", "gece");
        } else {
            document.documentElement.removeAttribute("data-wordnest-theme");
            tema = "gunduz";
        }
        try {
            localStorage.setItem(anahtar, tema);
        } catch (e) {}
        var dil = "tr";
        try { dil = localStorage.getItem("wordnest-dil") || "tr"; } catch (e) {}
        dugme.textContent = tema === "gece" ? "☀️" : "🌙";
        dugme.setAttribute("aria-pressed", tema === "gece" ? "true" : "false");
        var etiket = tema === "gece"
            ? (dil === "en" ? "Switch to day mode" : "Gündüz moduna geç")
            : (dil === "en" ? "Switch to night mode" : "Gece moduna geç");
        dugme.setAttribute("aria-label", etiket);
        dugme.setAttribute("title", etiket);
    }

    var dugme = document.createElement("button");
    dugme.type = "button";
    dugme.className = "wordnest-tema-dugmesi";
    dugme.addEventListener("click", function () {
        temayiUygula(tema === "gece" ? "gunduz" : "gece");
    });
    var dugmeKonumu = document.querySelector(".menu-butonlar") || document.querySelector(".ust-menu .container") || document.body;
    dugmeKonumu.appendChild(dugme);
    temayiUygula(tema);
})();

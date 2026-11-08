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
        dugme.textContent = tema === "gece" ? "☀️ Gündüz modu" : "🌙 Gece modu";
        dugme.setAttribute("aria-pressed", tema === "gece" ? "true" : "false");
        dugme.setAttribute("aria-label", tema === "gece" ? "Gündüz moduna geç" : "Gece moduna geç");
    }

    var dugme = document.createElement("button");
    dugme.type = "button";
    dugme.className = "wordnest-tema-dugmesi";
    dugme.addEventListener("click", function () {
        temayiUygula(tema === "gece" ? "gunduz" : "gece");
    });
    document.body.appendChild(dugme);
    temayiUygula(tema);
})();

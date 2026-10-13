/**
 * downloadPdf.js — Bouton de téléchargement de l'inventaire PDF.
 *
 * Lit les filtres saisis dans la partie « Consultation » (code-barre, type,
 * sous-type, nom), affiche une modale qui rappelle à l'utilisateur que ces
 * filtres seront appliqués à l'export, puis redirige vers
 * `php/generateInventoryPdf.php` avec les paramètres en query string.
 */

const btnDownloadPDF = document.getElementById("btn_download_pdf");

if (btnDownloadPDF) {
  btnDownloadPDF.addEventListener("click", async () => {
    const codeBarre = (document.getElementById("code_barre_consultation")?.value || "").trim();
    const type = (document.getElementById("type_materiel_consultation")?.value || "").trim();
    const sousType = (document.getElementById("sous_type_materiel_consultation")?.value || "").trim();
    const nom = (document.getElementById("nom_materiel_consultation")?.value || "").trim();

    const activeFilters = [];
    if (codeBarre) activeFilters.push(`• Code-barre : ${codeBarre}`);
    if (type) activeFilters.push(`• Type : ${type}`);
    if (sousType) activeFilters.push(`• Sous-type : ${sousType}`);
    if (nom) activeFilters.push(`• Nom : ${nom}`);

    const message = activeFilters.length === 0
      ? "Aucun filtre de consultation n'est saisi : l'inventaire complet sera exporté.\n\nAstuce : remplissez les champs de la partie « Consultation » (Code-barre, Type, Sous-type, Nom) avant de cliquer sur Télécharger pour n'exporter qu'une portion ciblée de l'inventaire."
      : `Attention : le PDF prend en compte les filtres saisis dans la partie « Consultation ».\n\nFiltres appliqués :\n${activeFilters.join("\n")}\n\nSeuls les matériels correspondants seront exportés.`;

    const confirmation = await showConfirm(message, {
      confirmText: "Télécharger",
      type: "info",
    });

    if (!confirmation) return;

    const params = new URLSearchParams();
    if (codeBarre) params.set("code_barre", codeBarre);
    if (type) params.set("type", type);
    if (sousType) params.set("sous_type", sousType);
    if (nom) params.set("nom", nom);

    const qs = params.toString();
    window.location.href = "php/generateInventoryPdf.php" + (qs ? `?${qs}` : "");
  });
}

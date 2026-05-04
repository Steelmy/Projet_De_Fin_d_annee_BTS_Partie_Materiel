/**
 * downloadPdf.js — Bouton de téléchargement de l'inventaire PDF.
 *
 * Demande confirmation puis redirige vers `php/generateInventoryPdf.php`
 * qui force le download via FPDF.
 */

const btnDownloadPDF = document.getElementById("btn_download_pdf");

if (btnDownloadPDF) {
  btnDownloadPDF.addEventListener("click", async () => {
    const confirmation = await showConfirm(
      "Voulez-vous télécharger l'inventaire complet en PDF ?",
      { confirmText: "Télécharger", type: "info" }
    );

    if (confirmation) {
      window.location.href = "php/generateInventoryPdf.php";
    }
  });
}

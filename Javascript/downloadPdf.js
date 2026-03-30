// Gestion du téléchargement du PDF
const btnDownloadPDF = document.getElementById("btn_download_pdf");

if (btnDownloadPDF) {
  btnDownloadPDF.addEventListener("click", async () => {
    const confirmation = await showConfirm(
      "Voulez-vous télécharger l'inventaire complet en PDF ?",
      { confirmText: "Télécharger", type: "info" }
    );

    if (confirmation) {
      // Rediriger vers le script PHP qui génère le PDF
      window.location.href = "php/generateInventoryPdf.php";
    }
  });
}

// Gestion du téléchargement du PDF
const btnDownloadPDF = document.getElementById("btn_download_pdf");

if (btnDownloadPDF) {
  btnDownloadPDF.addEventListener("click", () => {
    const confirmation = confirm(
      "Voulez-vous télécharger l'inventaire complet en PDF ?",
    );

    if (confirmation) {
      // Rediriger vers le script PHP qui génère le PDF
      window.location.href = "PHP/generate_inventory_pdf.php";
    }
  });
}

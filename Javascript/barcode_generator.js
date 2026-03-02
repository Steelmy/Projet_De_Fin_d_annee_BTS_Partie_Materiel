document.addEventListener("DOMContentLoaded", () => {
  // Récupération des éléments du DOM
  const btnOpenBarcode = document.getElementById("btn-open-barcode");
  const modal = document.getElementById("barcode-modal");
  const closeModal = document.querySelector(".close-modal");
  const qtySlider = document.getElementById("barcode-qty");
  const qtyVal = document.getElementById("qty-val");
  const printZone = document.getElementById("print-zone");
  const btnPrint = document.getElementById("btn-print");

  if (!btnOpenBarcode || !modal || !qtySlider || !printZone || !btnPrint) {
    console.error("Barcode generator: Missing elements", {
      btn: !!btnOpenBarcode,
      modal: !!modal,
      slider: !!qtySlider,
      zone: !!printZone,
      print: !!btnPrint,
    });
    return;
  }

  // Ouvrir la modale
  btnOpenBarcode.addEventListener("click", () => {
    modal.style.display = "block";

    // Reset à l'état initial (1 code-barre) à chaque ouverture
    qtySlider.value = 1;
    if (qtyVal) qtyVal.innerText = "1";
    generateBarcodes(1);
  });

  // Fermer la modale
  if (closeModal) {
    closeModal.addEventListener("click", () => {
      modal.style.display = "none";
    });
  }

  // Fermer la modale en cliquant en dehors
  window.addEventListener("click", (event) => {
    if (event.target == modal) {
      modal.style.display = "none";
    }
  });

  // Mettre à jour le texte du slider et générer l'aperçu
  qtySlider.addEventListener("input", (e) => {
    const qty = e.target.value;
    if (qtyVal) qtyVal.innerText = qty;
    generateBarcodes(qty);
  });

  function generateBarcodes(number) {
    printZone.innerHTML = ""; // On vide la zone

    for (let i = 1; i <= number; i++) {
      // Création d'un conteneur pour chaque code
      let container = document.createElement("div");
      container.className = "barcode-item";

      let svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
      svg.id = "barcode" + i;
      container.appendChild(svg);
      printZone.appendChild(container);

      // Génération d'un code numérique aléatoire (13 chiffres - pseudo EAN)
      // Pas de checksum calculé ici pour simplifier, juste 13 chiffres aléatoires
      let randomCode = "";
      for (let j = 0; j < 13; j++) {
        randomCode += Math.floor(Math.random() * 10);
      }

      // Génération du code-barre
      JsBarcode("#barcode" + i, randomCode, {
        format: "CODE128", // CODE128 accepte tout, mais on lui donne que des chiffres
        lineColor: "#000",
        width: 2,
        height: 40,
        displayValue: true,
      });
    }
  }

  // Lancer l'impression
  btnPrint.addEventListener("click", () => {
    window.print();
  });
});

document.addEventListener("DOMContentLoaded", () => {
  // Récupération des éléments du DOM
  const btnOpenBarcode = document.getElementById("btn-open-barcode");
  const modal = document.getElementById("barcode-modal");
  const closeModal = document.getElementById("close-barcode-modal");
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
    modal.classList.remove("hidden");
    modal.style.display = "flex";

    // Reset à l'état initial (1 code-barre) à chaque ouverture
    qtySlider.value = 1;
    if (qtyVal) qtyVal.innerText = "1";
    generateBarcodes(1);
  });

  // Fermer la modale
  if (closeModal) {
    closeModal.addEventListener("click", () => {
      modal.style.display = "none";
      modal.classList.add("hidden");
    });
  }

  // Fermer la modale en cliquant en dehors
  window.addEventListener("click", (event) => {
    if (event.target == modal) {
      modal.style.display = "none";
      modal.classList.add("hidden");
    }
  });

  // Mettre à jour le texte du slider et générer l'aperçu
  qtySlider.addEventListener("input", (e) => {
    const qty = e.target.value;
    if (qtyVal) qtyVal.innerText = qty;
    generateBarcodes(qty);
  });

  function generateBarcodes(number) {
    const num = parseInt(number, 10);
    const minVal = 1;
    const maxVal = 52;

    if (isNaN(num) || num < minVal || num > maxVal) {
      console.warn(
        `Quantité non autorisée : ${number}. Les valeurs doivent être comprises entre ${minVal} et ${maxVal}.`,
      );
      return;
    }

    printZone.innerHTML = ""; // On vide la zone

    for (let i = 1; i <= num; i++) {
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

  // Lancer l'impression propre
  btnPrint.addEventListener("click", () => {
    // Récupérer le contenu HTML des codes-barres
    const printContent = printZone.innerHTML;

    // S'il n'y a rien à imprimer
    if (!printContent.trim()) {
      console.warn("Rien à imprimer");
      return;
    }

    // Créer une fenêtre d'impression invisible (iframe)
    const printFrame = document.createElement("iframe");
    printFrame.style.position = "absolute";
    printFrame.style.width = "0";
    printFrame.style.height = "0";
    printFrame.style.border = "none";
    document.body.appendChild(printFrame);

    const doc = printFrame.contentWindow.document;

    // Injecter le contenu et les styles spécifiques pour l'impression 4 par ligne
    doc.write(`
      <html>
        <head>
          <title>Codes-barres</title>
          <style>
            body {
              margin: 0;
              padding: 20px;
              font-family: Arial, sans-serif;
            }
            .grid-container {
              display: grid;
              grid-template-columns: repeat(4, 1fr);
              gap: 20px;
              justify-items: center;
              align-items: center;
            }
            .barcode-item {
              page-break-inside: avoid;
              text-align: center;
            }
            svg {
              max-width: 100%;
              height: auto;
            }
          </style>
        </head>
        <body>
          <div class="grid-container">
            ${printContent}
          </div>
        </body>
      </html>
    `);

    doc.close();

    // Attendre que tout soit chargé, puis lancer l'impression
    printFrame.contentWindow.focus();
    printFrame.contentWindow.print();

    // Nettoyer l'iframe après un léger délai pour s'assurer que l'impression est partie
    setTimeout(() => {
      document.body.removeChild(printFrame);
    }, 1000);
  });
});

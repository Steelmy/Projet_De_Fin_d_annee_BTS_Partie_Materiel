document.addEventListener("DOMContentLoaded", () => {
  // Éléments du DOM
  const btnOpenBarcode = document.getElementById("btn-open-barcode");
  const modal = document.getElementById("barcode-modal");
  const closeModal = document.getElementById("close-barcode-modal");
  const btnLoad = document.getElementById("btn-load-barcodes");
  const btnClear = document.getElementById("btn-clear-print-zone");
  const btnPrint = document.getElementById("btn-print");
  const printZone = document.getElementById("print-zone");
  const barcodeCount = document.getElementById("barcode-count");
  const filterType = document.getElementById("barcode-filter-type");
  const filterSousType = document.getElementById("barcode-filter-sous-type");
  const filterNom = document.getElementById("barcode-filter-nom");

  let codesToPrint = new Set();
  let barcodeIndex = 0;

  if (!btnOpenBarcode || !modal || !printZone || !btnPrint) return;

  // === Ouverture de la modale ===
  btnOpenBarcode.addEventListener("click", () => {
    modal.classList.remove("hidden");
    modal.style.display = "flex";
    document.body.style.overflow = "hidden";


    // Reset filtres
    filterSousType.disabled = true;
    filterNom.disabled = true;
    codesToPrint.clear();
    printZone.innerHTML = "";
    barcodeIndex = 0;
    if (barcodeCount) barcodeCount.textContent = "0 code(s) à imprimer";

    // Charger les types disponibles
    chargerFiltreTypes();
  });

  // === Fermeture ===
  if (closeModal) {
    closeModal.addEventListener("click", fermerModale);
  }
  window.addEventListener("click", (event) => {
    if (event.target === modal) fermerModale();
  });

  function fermerModale() {
    modal.style.display = "none";
    modal.classList.add("hidden");
    document.body.style.overflow = "";
  }

  // === Charger les types dans le filtre ===
  async function chargerFiltreTypes() {
    try {
      const res = await fetch(
        "php/searchUniversal.php?type=materiel_type&query=",
      );
      const data = await res.json();
      filterType.innerHTML = '<option value="">Tous les types</option>';
      if (data.success && data.data) {
        data.data.forEach((r) => {
          const opt = document.createElement("option");
          opt.value = r.value;
          opt.textContent = r.label;
          filterType.appendChild(opt);
        });
      }
    } catch (e) {
      console.error("Erreur chargement types:", e);
    }
  }

  // === Cascade des filtres ===
  filterType.addEventListener("change", async () => {
    const type = filterType.value;
    filterSousType.innerHTML =
      '<option value="">Tous les sous-types</option>';
    filterNom.innerHTML = '<option value="">Tous les noms</option>';
    filterNom.disabled = true;

    if (!type) {
      filterSousType.disabled = true;
      return;
    }

    filterSousType.disabled = false;
    try {
      const res = await fetch(
        `php/searchUniversal.php?type=materiel_sous_type&query=&filter=${encodeURIComponent(type)}`,
      );
      const data = await res.json();
      if (data.success && data.data) {
        data.data.forEach((r) => {
          const opt = document.createElement("option");
          opt.value = r.value;
          opt.textContent = r.label;
          filterSousType.appendChild(opt);
        });
      }
    } catch (e) {
      console.error("Erreur chargement sous-types:", e);
    }
  });

  filterSousType.addEventListener("change", async () => {
    const type = filterType.value;
    const sousType = filterSousType.value;
    filterNom.innerHTML = '<option value="">Tous les noms</option>';

    if (!sousType) {
      filterNom.disabled = true;
      return;
    }

    filterNom.disabled = false;
    try {
      const res = await fetch(
        `php/searchUniversal.php?type=materiel_nom&query=&filter=${encodeURIComponent(type)}&filter_sous_type=${encodeURIComponent(sousType)}`,
      );
      const data = await res.json();
      if (data.success && data.data) {
        data.data.forEach((r) => {
          const opt = document.createElement("option");
          opt.value = r.value;
          opt.textContent = r.label;
          filterNom.appendChild(opt);
        });
      }
    } catch (e) {
      console.error("Erreur chargement noms:", e);
    }
  });

  // === Vider la liste d'impression ===
  if (btnClear) {
    btnClear.addEventListener("click", () => {
      codesToPrint.clear();
      barcodeIndex = 0;
      printZone.innerHTML = "";
      if (barcodeCount) barcodeCount.textContent = "0 code(s) à imprimer";
    });
  }

  // === Charger les codes-barres existants ===
  btnLoad.addEventListener("click", async () => {
    const params = new URLSearchParams();
    if (filterType.value) params.append("type", filterType.value);
    if (filterSousType.value) params.append("sous_type", filterSousType.value);
    if (filterNom.value) params.append("nom", filterNom.value);

    try {
      const res = await fetch(
        `php/generateBarcode.php?${params.toString()}`,
      );
      const data = await res.json();

      if (!data.success || !data.barcodes || data.barcodes.length === 0) {
        alert("Aucun code-barre trouvé pour ces critères.");
        return;
      }

      let addedCount = 0;

      data.barcodes.forEach((item) => {
        if (!codesToPrint.has(item.Code_bar)) {
          codesToPrint.add(item.Code_bar);
          addedCount++;
          
          const container = document.createElement("div");
          container.className = "barcode-item";

          // Label catégoriel au-dessus du code-barre
          const label = document.createElement("p");
          label.className = "text-xs text-gray-500 mb-1";
          label.textContent = [item.Type, item.Sous_type, item.Nom]
            .filter(Boolean)
            .join(" > ");
          container.appendChild(label);

          const svgId = "barcode-list-" + barcodeIndex++;
          const svg = document.createElementNS(
            "http://www.w3.org/2000/svg",
            "svg",
          );
          svg.id = svgId;
          container.appendChild(svg);
          printZone.appendChild(container);

          try {
            JsBarcode("#" + svgId, item.Code_bar, {
              format: "EAN13",
              lineColor: "#000",
              width: 2,
              height: 40,
              displayValue: true,
              fontSize: 14,
              margin: 10,
            });
          } catch (e) {
            JsBarcode("#" + svgId, item.Code_bar, {
              format: "CODE128",
              lineColor: "#000",
              width: 2,
              height: 40,
              displayValue: true,
              fontSize: 14,
              margin: 10,
            });
          }
        }
      });

      if (barcodeCount) {
        barcodeCount.textContent = `${codesToPrint.size} code(s) à imprimer`;
      }

    } catch (e) {
      console.error("Erreur chargement codes-barres:", e);
      alert("Erreur lors de l'ajout des codes-barres.");
    }
  });

  // === Impression ===
  btnPrint.addEventListener("click", () => {
    const printContent = printZone.innerHTML;

    if (!printContent.trim()) {
      alert("Aucun code-barre à imprimer. Veuillez d'abord charger les codes-barres.");
      return;
    }

    const printFrame = document.createElement("iframe");
    printFrame.style.position = "absolute";
    printFrame.style.width = "0";
    printFrame.style.height = "0";
    printFrame.style.border = "none";
    document.body.appendChild(printFrame);

    const doc = printFrame.contentWindow.document;

    doc.write(`
      <html>
        <head>
          <title>Codes-barres EAN-13</title>
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
    printFrame.contentWindow.focus();
    printFrame.contentWindow.print();

    setTimeout(() => {
      document.body.removeChild(printFrame);
    }, 1000);
  });
});

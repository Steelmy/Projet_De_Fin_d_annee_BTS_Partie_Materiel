document.addEventListener("DOMContentLoaded", () => {
  // Éléments du DOM
  const btnOpenBarcode = document.getElementById("btn-open-barcode");
  const modal = document.getElementById("barcode-modal");
  const closeModal = document.getElementById("close-barcode-modal");
  const btnClear = document.getElementById("btn-clear-print-zone");
  const btnPrint = document.getElementById("btn-print");
  const printZone = document.getElementById("print-zone");
  const barcodeCount = document.getElementById("barcode-count");
  const filterType = document.getElementById("barcode-filter-type");
  const filterSousType = document.getElementById("barcode-filter-sous-type");
  const filterNom = document.getElementById("barcode-filter-nom");

  let codesToPrint = new Set();
  let barcodeIndex = 0;
  
  // Variables pour le tableau filtrable
  let allInventoryBarcode = [];
  let barcodeCurrentPage = 1;
  const barcodeItemsPerPage = 10;
  let barcodeSortColumn = null;
  let barcodeSortDirection = "asc";
  let barcodeSelectedItems = new Map(); // id -> item

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
    barcodeSelectedItems.clear(); // Reset sélection tableau
    printZone.innerHTML = "";
    printZone.style.display = "none";
    btnPrint.style.display = "none";
    barcodeIndex = 0;
    if (barcodeCount) barcodeCount.textContent = "0 code(s) à imprimer";

    // Charger les types disponibles et l'inventaire
    chargerFiltreTypes();
    loadAllInventoryBarcode();
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
    resetBarcode();
  }

  // === Charger tout l'inventaire pour le tableau ===
  async function loadAllInventoryBarcode() {
    try {
      const response = await fetch("php/getAllItems.php");
      const data = await response.json();
      if (data.success && data.data) {
        allInventoryBarcode = data.data;
        applyBarcodeFilters();
      }
    } catch (error) {
      console.error("Erreur chargement inventaire pour codes-barres:", error);
    }
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
    
    applyBarcodeFilters();
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
    
    applyBarcodeFilters();
  });
  
  filterNom.addEventListener("change", () => {
    applyBarcodeFilters();
  });

  // === Tout réinitialiser (filtres + zone d'impression) ===
  function resetBarcode() {
    codesToPrint.clear();
    barcodeSelectedItems.clear();
    barcodeIndex = 0;
    printZone.innerHTML = "";
    printZone.style.display = "none";
    btnPrint.style.display = "none";

    // Reset filtres
    filterType.value = "";
    filterSousType.innerHTML = '<option value="">Tous les sous-types</option>';
    filterSousType.disabled = true;
    filterNom.innerHTML = '<option value="">Tous les noms</option>';
    filterNom.disabled = true;

    // Uncheck all checkboxes in the table
    document.querySelectorAll(".bc-checkbox").forEach((cb) => {
      cb.checked = false;
    });
    const selectAll = document.getElementById("bc_select_all");
    if (selectAll) selectAll.checked = false;

    if (barcodeCount) barcodeCount.textContent = "0 code(s) à imprimer";

    // Réappliquer les filtres pour réafficher tout l'inventaire
    barcodeCurrentPage = 1;
    applyBarcodeFilters();
  }

  // === Vider la liste d'impression ===
  if (btnClear) {
    btnClear.addEventListener("click", resetBarcode);
  }

  // === Fonction de Filtrage Local pour le Tableau ===
  function applyBarcodeFilters() {
    const typeValue = filterType.value.trim();
    const sousTypeValue = filterSousType.value.trim();
    const nomValue = filterNom.value.trim();

    let filtered = [...allInventoryBarcode];

    if (typeValue) filtered = filtered.filter((item) => item.Type === typeValue);
    if (sousTypeValue) filtered = filtered.filter((item) => item.Sous_type === sousTypeValue);
    if (nomValue) filtered = filtered.filter((item) => item.Nom === nomValue);

    // Tri (utilise sortUtils.js si dispo, ou tri basique)
    if (barcodeSortColumn) {
      if (window.localeSortComparator) {
        filtered.sort((a, b) => window.localeSortComparator(a, b, barcodeSortColumn, barcodeSortDirection));
      } else {
        filtered.sort((a, b) => {
          let valA = a[barcodeSortColumn] || "";
          let valB = b[barcodeSortColumn] || "";
          if (valA < valB) return barcodeSortDirection === "asc" ? -1 : 1;
          if (valA > valB) return barcodeSortDirection === "asc" ? 1 : -1;
          return 0;
        });
      }
    }

    renderBarcodeTable(filtered);
  }

  // === Rendu du Tableau avec Checkboxes ===
  function renderBarcodeTable(filtered) {
    const container = document.getElementById("barcode_table_container");
    if (!container) return;

    const totalItems = filtered.length;
    const totalPages = Math.ceil(totalItems / barcodeItemsPerPage) || 1;

    if (barcodeCurrentPage > totalPages) barcodeCurrentPage = totalPages;
    if (barcodeCurrentPage < 1) barcodeCurrentPage = 1;

    const startIndex = (barcodeCurrentPage - 1) * barcodeItemsPerPage;
    const endIndex = startIndex + barcodeItemsPerPage;
    const paginatedItems = filtered.slice(startIndex, endIndex);

    const getSortIcon = (col) => {
      if (barcodeSortColumn === col) {
        return barcodeSortDirection === "asc" ? "↑" : "↓";
      }
      return '<span class="opacity-50">↕</span>';
    };

    let html = `
      <table class="w-full border-collapse bg-white shadow-input rounded-lg overflow-hidden text-sm">
        <thead class="bg-linear-to-br from-custom-brandLight to-custom-brandDark text-white select-none">
          <tr>
            <th class="p-3 text-left font-semibold w-12 border-r border-white/20">
              <input type="checkbox" id="bc_select_all" class="rounded border-white/40 text-custom-primary focus:ring-white" />
            </th>
            <th class="p-3 font-semibold text-center cursor-pointer border-r border-white/20" onclick="window.sortBarcodeTable('Code_bar')">
              Code-barre ${getSortIcon("Code_bar")}
            </th>
            <th class="p-3 font-semibold text-center cursor-pointer border-r border-white/20" onclick="window.sortBarcodeTable('Type')">
              Type ${getSortIcon("Type")}
            </th>
            <th class="p-3 font-semibold text-center cursor-pointer border-r border-white/20" onclick="window.sortBarcodeTable('Sous_type')">
              Sous-type ${getSortIcon("Sous_type")}
            </th>
            <th class="p-3 font-semibold text-center cursor-pointer border-r border-white/20" onclick="window.sortBarcodeTable('Nom')">
              Nom ${getSortIcon("Nom")}
            </th>
            <th class="p-3 font-semibold text-center cursor-pointer" onclick="window.sortBarcodeTable('created_at')">
              Date de création ${getSortIcon("created_at")}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
    `;

    if (paginatedItems.length === 0) {
      html += `<tr><td colspan="6" class="p-4 text-center text-gray-400 italic">Aucun objet trouvé pour ces critères</td></tr>`;
    } else {
      paginatedItems.forEach((objet) => {
        const isSelected = barcodeSelectedItems.has(objet.id);
        const objetJson = JSON.stringify(objet).replace(/'/g, "&apos;");
        html += `
          <tr class="cursor-pointer" onclick="document.getElementById('bc_cb_${objet.id}').click()">
            <td class="p-3 text-center border-r border-gray-100" onclick="event.stopPropagation()">
              <input type="checkbox" id="bc_cb_${objet.id}" 
                     class="bc-checkbox rounded border-gray-300 text-custom-primary focus:ring-custom-primary" 
                     data-objet='${objetJson}'
                     ${isSelected ? "checked" : ""} />
            </td>
            <td class="p-3 text-center border-r border-gray-100">${objet.Code_bar}</td>
            <td class="p-3 text-center border-r border-gray-100">${objet.Type}</td>
            <td class="p-3 text-center border-r border-gray-100">${objet.Sous_type || "-"}</td>
            <td class="p-3 text-center border-r border-gray-100 text-gray-600">${objet.Nom}</td>
            <td class="p-3 text-center text-gray-600">${objet.created_at ? new Date(objet.created_at).toLocaleDateString('fr-FR') : "-"}</td>
          </tr>
        `;
      });
    }

    html += `</tbody></table>`;

    // Pagination
    html += `
      <div class="p-3 flex justify-between items-center text-slate-500 text-sm border border-t-0 border-gray-200 mt-0 bg-gray-50 rounded-b-lg shadow-input">
        <button type="button" class="px-3 py-1 bg-white border border-[#ccc] rounded-lg cursor-pointer hover:bg-gray-50 disabled:opacity-50" 
                onclick="window.changeBarcodePage(-1)" ${barcodeCurrentPage === 1 ? "disabled" : ""}>
          &larr; Précédent
        </button>
        <span class="font-medium">Page ${barcodeCurrentPage} / ${totalPages} <span class="text-xs ml-2">(Total: ${totalItems})</span></span>
        <button type="button" class="px-3 py-1 bg-white border border-[#ccc] rounded-lg cursor-pointer hover:bg-gray-50 disabled:opacity-50" 
                onclick="window.changeBarcodePage(1)" ${barcodeCurrentPage === totalPages ? "disabled" : ""}>
          Suivant &rarr;
        </button>
      </div>
    `;

    container.innerHTML = html;

    // Gérer sélection multiple "Select All"
    const selectAll = document.getElementById("bc_select_all");
    if (selectAll) {
      selectAll.addEventListener("change", (e) => {
        document.querySelectorAll(".bc-checkbox").forEach((cb) => {
          cb.checked = e.target.checked;
          const objet = JSON.parse(cb.dataset.objet.replace(/&apos;/g, "'"));
          if (cb.checked) {
            if (!barcodeSelectedItems.has(objet.id)) {
              barcodeSelectedItems.set(objet.id, objet);
              toggleBarcodeInPrintZone(objet, true);
            }
          } else {
            if (barcodeSelectedItems.has(objet.id)) {
              barcodeSelectedItems.delete(objet.id);
              toggleBarcodeInPrintZone(objet, false);
            }
          }
        });
        updatePrintZoneVisibility();
      });
    }

    // Checkbox individuelle
    document.querySelectorAll(".bc-checkbox").forEach((cb) => {
      cb.addEventListener("change", (e) => {
        const objet = JSON.parse(e.target.dataset.objet.replace(/&apos;/g, "'"));
        if (e.target.checked) {
          barcodeSelectedItems.set(objet.id, objet);
          toggleBarcodeInPrintZone(objet, true);
        } else {
          barcodeSelectedItems.delete(objet.id);
          toggleBarcodeInPrintZone(objet, false);
        }
        updatePrintZoneVisibility();
      });
    });
  }

  // --- Helpers de pagination & tri (rendus globaux pour le HTML) ---
  window.changeBarcodePage = function (delta) {
    barcodeCurrentPage += delta;
    applyBarcodeFilters();
  };

  window.sortBarcodeTable = function (column) {
    if (barcodeSortColumn === column) {
      barcodeSortDirection = barcodeSortDirection === "asc" ? "desc" : "asc";
    } else {
      barcodeSortColumn = column;
      barcodeSortDirection = "asc";
    }
    barcodeCurrentPage = 1;
    applyBarcodeFilters();
  };

  // === Gérer la zone d'impression dynamiquement ===
  function toggleBarcodeInPrintZone(item, add) {
    if (add) {
      if (!codesToPrint.has(item.id)) {
        codesToPrint.add(item.id);
        
        const container = document.createElement("div");
        container.className = "barcode-item flex flex-col items-center p-2 border border-gray-100 rounded bg-white shadow-sm";
        container.id = "barcode-item-container-" + item.id;

        // Label catégoriel au-dessus du code-barre
        const label = document.createElement("p");
        label.className = "text-xs text-center text-gray-500 mb-2 truncate max-w-[200px]";
        label.textContent = [item.Type, item.Sous_type, item.Nom]
          .filter(Boolean)
          .join(" > ");
        container.appendChild(label);

        const svgId = "barcode-svg-" + item.id;
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
          try {
            JsBarcode("#" + svgId, item.Code_bar, {
              format: "CODE128",
              lineColor: "#000",
              width: 2,
              height: 40,
              displayValue: true,
              fontSize: 14,
              margin: 10,
            });
          } catch(err) {
            console.error("Erreur de génération du code-barre :", item.Code_bar);
          }
        }
      }
    } else {
      if (codesToPrint.has(item.id)) {
        codesToPrint.delete(item.id);
        const container = document.getElementById("barcode-item-container-" + item.id);
        if (container) {
          container.remove();
        }
      }
    }
  }

  function updatePrintZoneVisibility() {
    if (codesToPrint.size > 0) {
      printZone.style.display = "flex";
      btnPrint.style.display = "inline-block";
    } else {
      printZone.style.display = "none";
      btnPrint.style.display = "none";
    }
    if (barcodeCount) {
      barcodeCount.textContent = `${codesToPrint.size} code(s) à imprimer`;
    }
  }

  // === Impression ===
  btnPrint.addEventListener("click", () => {
    const printContent = printZone.innerHTML;

    if (!printContent.trim()) {
      showAlert("Aucun code-barre à imprimer. Veuillez d'abord charger les codes-barres.", "warning");
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

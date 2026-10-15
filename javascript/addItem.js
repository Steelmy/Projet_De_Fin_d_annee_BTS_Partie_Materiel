/**
 * addItem.js — Soumission du formulaire d'ajout de matériel
 * et impression optionnelle des codes-barres EAN-13 générés.
 */

const typeInputAjout = document.getElementById("type_materiel_ajout");
const nomInputAjout = document.getElementById("nom_materiel_ajout");
const formAjout = document.getElementById("form_ajout");
const nombreInputAjout = document.getElementById("nombre_materiel");

/** Quantité maximale par ajout, lue sur l'attribut `max` du champ (vérifiée aussi côté serveur). */
const MAX_NOMBRE_AJOUT = parseInt(nombreInputAjout.max, 10) || 30;

/** Bouton « Ajouter » et verrou anti double-soumission (double-clic, Entrée répétée). */
const submitBtnAjout = formAjout.querySelector('button[type="submit"]');
let ajoutEnCours = false;

// L'attribut `max` n'empêche pas la saisie au clavier : on ramène la valeur dans [1, max].
nombreInputAjout.addEventListener("input", () => {
  const digits = nombreInputAjout.value.replace(/\D/g, "");
  if (digits === "") return;
  const valeur = parseInt(digits, 10);
  if (valeur > MAX_NOMBRE_AJOUT) {
    nombreInputAjout.value = MAX_NOMBRE_AJOUT;
  } else if (digits !== nombreInputAjout.value) {
    nombreInputAjout.value = valeur;
  }
});

nombreInputAjout.addEventListener("blur", () => {
  const valeur = parseInt(nombreInputAjout.value, 10);
  if (!valeur || valeur < 1) nombreInputAjout.value = 1;
});

formAjout.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (ajoutEnCours) return;

  const type = typeInputAjout.value.trim();
  const nom = nomInputAjout.value.trim();
  const nombre = parseInt(nombreInputAjout.value, 10);

  if (!type) {
    await showAlert("Veuillez entrer un type de matériel", "warning");
    return;
  }

  if (!nom) {
    await showAlert("Veuillez entrer un nom de matériel", "warning");
    return;
  }

  if (!nombre || nombre <= 0) {
    await showAlert("Veuillez entrer un nombre valide", "warning");
    return;
  }

  if (nombre > MAX_NOMBRE_AJOUT) {
    await showAlert(`Impossible d'ajouter plus de ${MAX_NOMBRE_AJOUT} matériels à la fois`, "warning");
    return;
  }

  ajoutEnCours = true;
  if (submitBtnAjout) submitBtnAjout.disabled = true;

  try {
    const formData = new FormData();
    formData.append("type_materiel", type);

    const sousTypeInput = document.getElementById("sous_type_materiel_ajout");
    if (sousTypeInput) {
      formData.append("sous_type_materiel", sousTypeInput.value.trim());
    }

    formData.append("nom_materiel", nom);
    formData.append("nombre", nombre);

    const response = await fetch("php/addItem.php", {
      method: "POST",
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      const codes = data.codes_barres_generes || [];
      const codesStr = codes.join(", ");

      const imprimerMaintenant = await showConfirm(
        data.message +
          "\n\nCodes-barres EAN-13 générés :\n" +
          codesStr +
          "\n\nVoulez-vous imprimer les étiquettes maintenant ?",
        { confirmText: "Imprimer", cancelText: "Plus tard", type: "success" },
      );

      if (imprimerMaintenant && codes.length > 0) {
        imprimerCodesBarres(codes);
      }

      typeInputAjout.value = "";
      if (sousTypeInput) sousTypeInput.value = "";
      nomInputAjout.value = "";
      nombreInputAjout.value = "1";

      if (sousTypeInput) sousTypeInput.disabled = true;
      if (nomInputAjout) nomInputAjout.disabled = true;

      if (window.rechargerTousLesTypes) {
        window.rechargerTousLesTypes();
      }

      if (window.refreshInventory) {
        window.refreshInventory();
      }
    } else {
      await showAlert("Erreur: " + data.message, "error");
    }
  } catch (error) {
    console.error("Erreur lors de l'ajout:", error);
    await showAlert("Erreur lors de l'ajout du matériel", "error");
  } finally {
    ajoutEnCours = false;
    if (submitBtnAjout) submitBtnAjout.disabled = false;
  }
});

/**
 * Imprime une liste de codes-barres EAN-13 via une iframe d'impression.
 * Bascule automatiquement en CODE128 si la génération EAN-13 échoue
 * (cas d'un code non conforme).
 *
 * @param {string[]} codes - Liste de codes EAN-13 à imprimer.
 * @returns {void}
 */
function imprimerCodesBarres(codes) {
  const tempDiv = document.createElement("div");
  tempDiv.style.display = "none";
  document.body.appendChild(tempDiv);

  codes.forEach((code, i) => {
    const svgId = "print-ean-" + i;
    const container = document.createElement("div");
    container.className = "barcode-item";
    const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
    svg.id = svgId;
    container.appendChild(svg);
    tempDiv.appendChild(container);

    try {
      JsBarcode("#" + svgId, code, {
        format: "EAN13",
        lineColor: "#000",
        width: 2,
        height: 40,
        displayValue: true,
        fontSize: 14,
        margin: 10,
      });
    } catch (e) {
      JsBarcode("#" + svgId, code, {
        format: "CODE128",
        lineColor: "#000",
        width: 2,
        height: 40,
        displayValue: true,
        fontSize: 14,
        margin: 10,
      });
    }
  });

  const printContent = tempDiv.innerHTML;

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
    document.body.removeChild(tempDiv);
  }, 1000);
}

window.imprimerCodesBarres = imprimerCodesBarres;

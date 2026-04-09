// ===== GESTION DU FORMULAIRE D'AJOUT =====
const typeInputAjout = document.getElementById("type_materiel_ajout");
const nomInputAjout = document.getElementById("nom_materiel_ajout");
const formAjout = document.getElementById("form_ajout");

// Gérer la soumission du formulaire d'ajout
formAjout.addEventListener("submit", async (e) => {
  e.preventDefault();

  const type = typeInputAjout.value.trim();
  const nom = nomInputAjout.value.trim();
  const nombre = parseInt(document.getElementById("nombre_materiel").value);

  // Vérifier que tous les champs sont remplis
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

  try {
    const formData = new FormData();
    formData.append("type_materiel", type);

    const sousTypeInput = document.getElementById("sous_type_materiel_ajout");
    if (sousTypeInput) {
      formData.append("sous_type_materiel", sousTypeInput.value.trim());
    }

    formData.append("nom_materiel", nom);
    formData.append("nombre", nombre);

    // 1. Envoi de la requête HTTP POST au serveur avec les données du formulaire.
    // 'await' met en pause l'exécution de cette fonction jusqu'à ce que le serveur réponde.
    const response = await fetch("php/addItem.php", {
      method: "POST",
      body: formData,
    });

    // 2. Extraction du corps de la réponse renvoyée par le serveur.
    // '.json()' transforme le texte reçu (format JSON) en objet JavaScript.
    // L'exécution est à nouveau mise en pause jusqu'à la fin de cette extraction.
    const data = await response.json();

    // 3. Traitement de la réponse si l'API indique un succès (data.success === true)
    if (data.success) {
      // Récupération des codes-barres générés renvoyés par l'API
      const codes = data.codes_barres_generes || [];
      const codesStr = codes.join(", ");

      // Proposer l'impression immédiate via une modale interactive
      const imprimerMaintenant = await showConfirm(
        data.message +
          "\n\nCodes-barres EAN-13 générés :\n" +
          codesStr +
          "\n\nVoulez-vous imprimer les étiquettes maintenant ?",
        { confirmText: "Imprimer", cancelText: "Plus tard", type: "success" },
      );

      // Si l'utilisateur a cliqué sur "Imprimer", on déclenche l'impression
      if (imprimerMaintenant && codes.length > 0) {
        imprimerCodesBarres(codes);
      }

      // 4. Nettoyage de l'interface utilisateur (UI)
      // Réinitialiser les champs du formulaire après l'ajout
      typeInputAjout.value = "";
      if (sousTypeInput) sousTypeInput.value = "";
      nomInputAjout.value = "";
      document.getElementById("nombre_materiel").value = "1";

      // Désactiver les listes déroulantes (selects) qui dépendent du type principal
      if (sousTypeInput) sousTypeInput.disabled = true;
      if (nomInputAjout) nomInputAjout.disabled = true;

      // Forcer la mise à jour des données globales si les fonctions existent sur la page
      if (window.rechargerTousLesTypes) {
        window.rechargerTousLesTypes();
      }

      if (window.refreshInventory) {
        window.refreshInventory();
      }
    } else {
      // 5. Gestion des erreurs renvoyées volontairement par le serveur 
      // (ex: champs manquants contrôlés par le PHP)
      await showAlert("Erreur: " + data.message, "error");
    }
  } catch (error) {
    // 6. Gestion des blocages et pannes (Erreurs inattendues)
    // Capture les coupures réseau, les plantages serveurs (Erreur 500), 
    // ou si '.json()' plante parce que le serveur n'a pas renvoyé du JSON (ex: erreur fatale PHP)
    console.error("Erreur lors de l'ajout:", error);
    await showAlert("Erreur lors de l'ajout du matériel", "error");
  }
});

/**
 * Imprime une liste de codes-barres EAN-13 via une iframe d'impression.
 */
function imprimerCodesBarres(codes) {
  // Créer un conteneur temporaire
  const tempDiv = document.createElement("div");
  tempDiv.style.display = "none";
  document.body.appendChild(tempDiv);

  // Générer les SVG pour chaque code
  let svgHtml = "";
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
      // Fallback en CODE128 si EAN13 échoue
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

  // Récupérer le HTML généré
  const printContent = tempDiv.innerHTML;

  // Créer l'iframe d'impression
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

// Exposer la fonction pour le générateur
window.imprimerCodesBarres = imprimerCodesBarres;

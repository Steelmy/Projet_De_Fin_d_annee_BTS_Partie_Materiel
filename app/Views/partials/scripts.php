<script>
  /* Fonction pour ouvrir/fermer la sidebar */
  function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('hidden');
      const mainContent = document.getElementById('mainContent');
      if (mainContent) {
          mainContent.classList.toggle('full');
      }
  }

  // Logique de switching des modes UI
  document.addEventListener("DOMContentLoaded", () => {
    const modeSelector = document.getElementById("modeSelector");
    const forms = {
      ajout: document.getElementById("form_ajout"),
      suppression: document.getElementById("form_suppression"),
      modification: document.getElementById("form_modification"),
      consultation: document.getElementById("filters_consultation"),
    };
    const panels = {
      ajout: document.getElementById("panel_ajout_caisse"),
      suppression: document.getElementById("panel_suppression_caisse"),
      modification: document.getElementById("panel_modification_caisse"),
    };

    const updateUI = () => {
      const mode = modeSelector.value;
      Object.values(forms).forEach((f) => f && f.classList.add("hidden"));
      Object.values(panels).forEach((p) => p && p.classList.add("hidden"));

      if (forms[mode]) {
        const isGridChecked = document.getElementById("toggle_caisse_view")?.checked;
        if (mode === "consultation" && isGridChecked) {
          // Ne pas afficher les filtres si la vue grille est active
        } else {
          forms[mode].classList.remove("hidden");
        }
      }
    };

    modeSelector.addEventListener("change", updateUI);
    updateUI();

    // Toggle vue caisses (Commented out)
    /*
    document
      .getElementById("toggle_caisse_view")
      .addEventListener("change", (e) => {
        const isGrid = e.target.checked;
        document.getElementById("full_inventory").classList.toggle("hidden", isGrid);
        document.getElementById("caisses_view").classList.toggle("hidden", !isGrid);

        const leg = document.getElementById("toggle_caisse_consultation");
        if (leg) {
          leg.checked = isGrid;
          leg.dispatchEvent(new Event("change"));
        }
      });
    */

    // Liaison des panneaux caisse
    const bindPanel = (chkId, pnlId) => {
      const chk = document.getElementById(chkId);
      if (chk)
        chk.addEventListener("change", (e) => {
          const p = document.getElementById(pnlId);
          if (p) {
            if (e.target.checked) p.classList.remove("hidden");
            else p.classList.add("hidden");
          }
          if (chkId === "toggle_caisse_suppression") {
            const grp = document.getElementById("caisse_input_group_suppr");
            if (grp) {
              if (e.target.checked) grp.classList.remove("hidden");
              else grp.classList.add("hidden");
            }
          }
        });
    };
    bindPanel("toggle_caisse_ajout", "panel_ajout_caisse");
    bindPanel("toggle_caisse_suppression", "panel_suppression_caisse");
    bindPanel("toggle_caisse_modification", "panel_modification_caisse");
  });

  // Legacy global
  window.switchTab = (t) => {
    const s = document.getElementById("modeSelector");
    if (s) {
      s.value = t;
      s.dispatchEvent(new Event("change"));
    }
  };
</script>

<!-- Librairies externes -->
<script src="https://unpkg.com/lucide@latest"></script>
<script>lucide.createIcons();</script>
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>

<!-- JavaScript applicatif -->
<script src="javascript/customModal.js?v=1"></script>
<script src="javascript/sessionGuard.js?v=1"></script>
<script src="javascript/sortUtils.js"></script>
<script src="javascript/barcodeGenerator.js?v=3"></script>
<script src="javascript/universalAutocomplete.js?v=2"></script>
<script src="javascript/universalAutocompleteBarcode.js?v=5"></script>
<script src="javascript/initAutocompletes.js?v=2"></script>
<!-- <script src="javascript/addBox.js?v=2"></script> -->
<!-- <script src="javascript/deleteBox.js"></script> -->
<!-- <script src="javascript/updateBox.js?v=2"></script> -->
<script src="javascript/textFieldLoader.js?v=2"></script>
<script src="javascript/deleteItem.js"></script>
<script src="javascript/updateItem.js?v=2"></script>
<script src="javascript/filterConsultation.js"></script>
<script src="javascript/downloadPdf.js"></script>
<script src="javascript/addItem.js?v=3"></script>
<script src="javascript/formActions.js?v=2"></script>
<!-- <script src="javascript/boxFormToggle.js?v=1"></script> -->
<script src="javascript/dynamicSelects.js"></script>
<script src="javascript/referenceManager.js?v=5"></script>

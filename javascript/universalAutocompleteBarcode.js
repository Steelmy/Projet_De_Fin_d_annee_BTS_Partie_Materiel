/**
 * universalAutocompleteBarcode.js — Autocomplete dédié aux codes-barres
 * avec filtres en cascade (type/sous-type/nom) et options d'état/disponibilité.
 *
 * Le conteneur de suggestions est attaché au `<body>` pour éviter les
 * problèmes de z-index / overflow avec les modales.
 */
class UniversalAutocompleteBarcode {
  /**
   * @param {string} inputId - ID du champ code-barre.
   * @param {string|null} containerId - ID d'un conteneur existant, ou null pour création auto.
   * @param {string|null} [typeInputId=null] - ID du champ Type (filtre dynamique).
   * @param {string|null} [sousTypeInputId=null] - ID du champ Sous-type (filtre dynamique).
   * @param {string|null} [nomInputId=null] - ID du champ Nom (filtre dynamique).
   * @param {(item: object) => void} [onSelectCallback=null] - Callback après sélection (reçoit l'item complet).
   * @param {string|null} [etatFilter=null] - Filtre d'état exact côté serveur.
   * @param {boolean} [disponibleOnly=false] - Restreint aux objets disponibles hors caisse.
   * @param {boolean} [nonDisponibleOnly=false] - Restreint aux objets emprunté/réservé hors caisse.
   */
  constructor(
    inputId,
    containerId,
    typeInputId = null,
    sousTypeInputId = null,
    nomInputId = null,
    onSelectCallback = null,
    etatFilter = null,
    disponibleOnly = false,
    nonDisponibleOnly = false
  ) {
    this.input = document.getElementById(inputId);
    if (!this.input) return;

    this.typeInput = typeInputId ? document.getElementById(typeInputId) : null;
    this.sousTypeInput = sousTypeInputId ? document.getElementById(sousTypeInputId) : null;
    this.nomInput = nomInputId ? document.getElementById(nomInputId) : null;
    this.onSelectCallback = onSelectCallback;
    this.etatFilter = etatFilter;
    this.disponibleOnly = disponibleOnly;
    this.nonDisponibleOnly = nonDisponibleOnly;
    this.debounceTimer = null;
    this.containerClass = "autocomplete-suggestions";

    this.container = containerId ? document.getElementById(containerId) : null;
    if (!this.container) {
      this.initContainer();
    }

    this.initEvents();
    this.input.dataset.barcodeInitialized = "true";
    console.log(`UniversalAutocompleteBarcode initialized on #${inputId}`);
  }

  /**
   * Crée (si besoin) et déplace le conteneur de suggestions dans `<body>`.
   *
   * @returns {void}
   */
  initContainer() {
    let container = document.querySelector(
      `#${this.input.id}_autocomplete_container`,
    );

    if (!container) {
      container = document.createElement("div");
      container.id = `${this.input.id}_autocomplete_container`;
      container.className = this.containerClass;
    }

    container.style.display = "none";

    if (container.parentNode !== document.body) {
      document.body.appendChild(container);
    }

    this.container = container;
  }

  /**
   * Recalcule la position absolue du conteneur sous l'input.
   *
   * @returns {void}
   */
  updatePosition() {
    if (!this.container || !this.input) return;

    const rect = this.input.getBoundingClientRect();
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollLeft =
      window.pageXOffset || document.documentElement.scrollLeft;

    this.container.style.top = `${rect.bottom + scrollTop}px`;
    this.container.style.left = `${rect.left + scrollLeft}px`;
    this.container.style.width = `${rect.width}px`;
  }

  /**
   * Câble les événements de saisie, focus et clic extérieur.
   *
   * @returns {void}
   */
  initEvents() {
    this.input.addEventListener("keypress", (e) => {
      const charCode = e.which ? e.which : e.keyCode;
      if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        e.preventDefault();
      }
    });
    this.input.addEventListener("input", () => {
      this.input.value = this.input.value.replace(/[^0-9]/g, "");
      this.handleInput();
    });

    this.input.addEventListener("focus", () => {
      this.search(this.input.value);
    });

    document.addEventListener("click", (e) => {
      if (e.target !== this.input && e.target !== this.container) {
        this.hide();
      }
    });
  }

  /**
   * Déclenche une recherche debouncée à la saisie.
   *
   * @returns {void}
   */
  handleInput() {
    clearTimeout(this.debounceTimer);
    const query = this.input.value.trim();
    this.debounceTimer = setTimeout(() => {
      this.search(query);
    }, 300);
  }

  /**
   * Construit l'URL avec les filtres dynamiques et appelle searchBarcodes.php.
   *
   * @param {string} query - Préfixe de code-barres.
   * @returns {Promise<void>}
   */
  async search(query) {
    const typeVal = this.typeInput ? this.typeInput.value.trim() : "";
    const sousTypeVal = this.sousTypeInput ? this.sousTypeInput.value.trim() : "";
    const nomVal = this.nomInput ? this.nomInput.value.trim() : "";

    try {
      let url = `php/searchBarcodes.php?query=${encodeURIComponent(query)}`;
      if (typeVal) url += `&type=${encodeURIComponent(typeVal)}`;
      if (sousTypeVal) url += `&sous_type=${encodeURIComponent(sousTypeVal)}`;
      if (nomVal) url += `&nom=${encodeURIComponent(nomVal)}`;
      if (this.etatFilter)
        url += `&etat=${encodeURIComponent(this.etatFilter)}`;
      if (this.disponibleOnly) url += `&disponible_only=1`;
      if (this.nonDisponibleOnly) url += `&non_disponible_only=1`;

      const response = await fetch(url);
      const data = await response.json();

      if (data.success && data.results.length > 0) {
        this.displayResults(data.results);
      } else {
        this.hide();
      }
    } catch (error) {
      console.error("Erreur Autocomplete Barcode:", error);
    }
  }

  /**
   * Construit le DOM des suggestions et le repositionne sous l'input.
   *
   * @param {Array<{Code_bar:string, Type:string, Sous_type:string, Nom:string}>} items - Lignes renvoyées par l'API.
   * @returns {void}
   */
  displayResults(items) {
    this.updatePosition();
    this.container.innerHTML = "";
    this.container.style.display = "block";

    items.forEach((item) => {
      const div = document.createElement("div");
      div.className = "autocomplete-suggestion";
      div.innerHTML = `<strong class="font-bold">${item.Code_bar}</strong> <span class="text-[0.8em] text-[#666]">(${item.Type} - ${item.Nom})</span>`;

      div.addEventListener("click", (e) => {
        e.stopPropagation();
        this.select(item);
      });

      this.container.appendChild(div);
    });
  }

  /**
   * Applique la sélection : remplit l'input avec le code-barres, déclenche le callback.
   *
   * @param {{Code_bar:string, Type:string, Sous_type:string, Nom:string}} item - Item sélectionné.
   * @returns {void}
   */
  select(item) {
    this.input.value = item.Code_bar;
    this.hide();
    if (this.onSelectCallback) {
      this.onSelectCallback(item);
    }
  }

  /**
   * Masque le conteneur de suggestions.
   *
   * @returns {void}
   */
  hide() {
    this.container.style.display = "none";
  }
}

window.UniversalAutocompleteBarcode = UniversalAutocompleteBarcode;

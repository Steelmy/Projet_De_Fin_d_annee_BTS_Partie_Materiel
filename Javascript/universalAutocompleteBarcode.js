class UniversalAutocompleteBarcode {
  /**
   * @param {string} inputId - ID du champ code-barre
   * @param {string} containerId - ID du conteneur de suggestions (optionnel)
   * @param {string} typeInputId - ID du champ Type (pour filtre)
   * @param {string} nomInputId - ID du champ Nom (pour filtre)
   * @param {function} onSelectCallback - Fonction appelée après sélection
   */
  constructor(
    inputId,
    containerId,
    typeInputId = null,
    nomInputId = null,
    onSelectCallback = null,
    etatFilter = null,
    disponibleOnly = false,
  ) {
    this.input = document.getElementById(inputId);
    if (!this.input) return;

    this.typeInput = typeInputId ? document.getElementById(typeInputId) : null;
    this.nomInput = nomInputId ? document.getElementById(nomInputId) : null;
    this.onSelectCallback = onSelectCallback;
    this.etatFilter = etatFilter;
    this.disponibleOnly = disponibleOnly;
    this.debounceTimer = null;
    this.containerClass = "autocomplete-suggestions";

    // Création/Récupération Container
    this.container = containerId ? document.getElementById(containerId) : null;
    if (!this.container) {
      this.initContainer();
    }

    this.initEvents();
    this.input.dataset.barcodeInitialized = "true";
    console.log(`UniversalAutocompleteBarcode initialized on #${inputId}`);
  }

  initContainer() {
    // On utilise le body pour éviter les problèmes de z-index/overflow
    let container = document.querySelector(
      `#${this.input.id}_autocomplete_container`,
    );

    if (!container) {
      container = document.createElement("div");
      container.id = `${this.input.id}_autocomplete_container`;
      container.className = this.containerClass;
    }

    // Force styles & position in BODY (même si existant)
    container.style.display = "none";

    // S'assurer qu'il est dans le body
    if (container.parentNode !== document.body) {
      document.body.appendChild(container);
    }

    this.container = container;
  }

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

  initEvents() {
    // 1. Force Numeric
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

    // 2. Focus (Click) -> Search (même vide)
    this.input.addEventListener("focus", () => {
      this.search(this.input.value);
    });

    // 3. Click Outside -> Hide
    document.addEventListener("click", (e) => {
      if (e.target !== this.input && e.target !== this.container) {
        this.hide();
      }
    });
  }

  handleInput() {
    clearTimeout(this.debounceTimer);
    const query = this.input.value.trim();
    this.debounceTimer = setTimeout(() => {
      this.search(query);
    }, 300);
  }

  async search(query) {
    // Récupération des filtres
    const typeVal = this.typeInput ? this.typeInput.value.trim() : "";
    const nomVal = this.nomInput ? this.nomInput.value.trim() : "";

    try {
      let url = `php/searchBarcodes.php?query=${encodeURIComponent(query)}`;
      if (typeVal) url += `&type=${encodeURIComponent(typeVal)}`;
      if (nomVal) url += `&nom=${encodeURIComponent(nomVal)}`;
      if (this.etatFilter)
        url += `&etat=${encodeURIComponent(this.etatFilter)}`;
      if (this.disponibleOnly) url += `&disponible_only=1`;

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

  displayResults(items) {
    this.updatePosition(); // Recalculer la position à l'affichage
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

  select(item) {
    this.input.value = item.Code_bar;
    this.hide();
    if (this.onSelectCallback) {
      this.onSelectCallback(item); // Passe tout l'objet (Code_bar, Type, Nom)
    }
  }

  hide() {
    this.container.style.display = "none";
  }
}

// Export global pour utilisation directe
window.UniversalAutocompleteBarcode = UniversalAutocompleteBarcode;

class UniversalAutocomplete {
  /**
   * @param {string} inputId - ID de l'input HTML
   * @param {string} type - 'user', 'caisse', 'materiel_type', 'materiel_sous_type', 'materiel_nom', 'materiel_code'
   * @param {function} onSelectCallback - Fonction appelée lors de la sélection (optionnel)
   * @param {function} filterCallback - Fonction qui retourne une valeur de filtre dynamique (optionnel, généralement Type)
   * @param {function} filterSousTypeCallback - Fonction qui retourne le sous-type pour filtre dynamique (optionnel)
   * @param {object} options - Options supplémentaires (ex: { numericOnly: true })
   */
  constructor(
    inputId,
    type,
    onSelectCallback = null,
    filterCallback = null,
    filterSousTypeCallback = null,
    options = {},
  ) {
    this.input = document.getElementById(inputId);
    this.type = type;
    this.onSelectCallback = onSelectCallback;
    this.filterCallback = filterCallback;
    this.filterSousTypeCallback = filterSousTypeCallback;
    this.options = options;
    this.containerClass = "autocomplete-suggestions";
    this.debounceTimer = null;
    this.isValidSelection = false; // Track if current value was selected from list

    if (this.input) {
      this.init();
    }
  }

  init() {
    // Validation numérique si demandée
    if (this.options.numericOnly) {
      this.input.addEventListener("keypress", (e) => {
        const charCode = e.which ? e.which : e.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
          e.preventDefault();
        }
      });
      // Pour le coller (paste)
      this.input.addEventListener("input", (e) => {
        if (this.options.numericOnly) {
          this.input.value = this.input.value.replace(/[^0-9]/g, "");
        }
      });
    }
    // Créer le conteneur de suggestions
    const parent = this.input.parentElement;

    // Vérifier si le parent est en position relative, sinon l'ajouter pour le bon positionnement
    const parentStyle = window.getComputedStyle(parent);
    if (parentStyle.position === "static") {
      parent.style.position = "relative";
    }

    let container = parent.querySelector(`.${this.containerClass}`);

    if (!container) {
      container = document.createElement("div");
      container.className = this.containerClass;
      container.style.display = "none";
      parent.appendChild(container);
    }

    this.container = container;

    // Événements
    this.input.addEventListener("input", () => this.handleInput());
    this.input.addEventListener("focus", () => this.handleFocus());
    
    // Strict mode blur verification
    this.input.addEventListener("blur", () => {
        if (this.options.strictMode && !this.isValidSelection && this.input.value.trim() !== "") {
            // Need a tiny timeout to allow click on suggestion to process first
            setTimeout(() => {
                if (!this.isValidSelection) {
                    this.input.value = ""; // Clear invalid input
                    // Trigger input event to notify listeners it was cleared
                    this.input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }, 150);
        }
    });

    // Fermeture au clic dehors
    document.addEventListener("click", (e) => {
      if (e.target !== this.input && e.target !== this.container) {
        this.hide();
      }
    });
  }

  handleFocus() {
    // Si champ vide, on lance une recherche "random" (handled by backend if query empty)
    // Si champ rempli, on relance la recherche courante pour réafficher les résultats
    this.search(this.input.value.trim());
  }

  handleInput() {
    this.isValidSelection = false; // Reset validity on manual input
    clearTimeout(this.debounceTimer);
    const query = this.input.value.trim();

    this.debounceTimer = setTimeout(() => {
      this.search(query);
    }, 200); // Debounce léger
  }

  async search(query) {
    try {
      // Récupérer le filtre dynamique si callback présent
      let filterVal = "";
      if (this.filterCallback && typeof this.filterCallback === "function") {
        filterVal = this.filterCallback();
      }
      
      let filterSousTypeVal = "";
      if (this.filterSousTypeCallback && typeof this.filterSousTypeCallback === "function") {
        filterSousTypeVal = this.filterSousTypeCallback();
      }

      // Construction de l'URL
      let url = `php/searchUniversal.php?type=${encodeURIComponent(this.type)}&query=${encodeURIComponent(query)}`;
      if (filterVal) {
        url += `&filter=${encodeURIComponent(filterVal)}`;
      }
      if (filterSousTypeVal) {
        url += `&filter_sous_type=${encodeURIComponent(filterSousTypeVal)}`;
      }

      const response = await fetch(url);
      const data = await response.json();

      if (data.success && data.data.length > 0) {
        this.displayResults(data.data);
      } else {
        this.hide();
        // Optionnel: Afficher un message "Aucun résultat, veuillez créer via Gestion des Références"
        if (this.options.strictMode && query !== "") {
            this.container.innerHTML = "<div class='p-2 text-sm text-gray-500 italic'>Non trouvé. Utilisez 'Gestion des Références'.</div>";
            this.container.style.display = "block";
        }
      }
    } catch (error) {
      console.error("Erreur UniversalAutocomplete:", error);
    }
  }

  displayResults(items) {
    this.container.innerHTML = "";
    this.container.style.display = "block";

    items.forEach((item) => {
      const div = document.createElement("div");
      div.className = "autocomplete-suggestion";
      div.textContent = item.label;

      div.addEventListener("click", (e) => {
        e.stopPropagation(); // Empêcher la propagation pour ne pas fermer tout de suite
        this.select(item);
      });

      this.container.appendChild(div);
    });
  }

  select(item) {
    this.input.value = item.value;
    this.isValidSelection = true;
    this.hide();

    // Callback utilisateur
    if (this.onSelectCallback) {
      this.onSelectCallback(item);
    }

    // Dispatch event générique pour compatibilité
    const event = new CustomEvent("autoCompleteSelected", { detail: item });
    this.input.dispatchEvent(event);
  }

  hide() {
    this.container.style.display = "none";
  }
}

// Export global
window.UniversalAutocomplete = UniversalAutocomplete;

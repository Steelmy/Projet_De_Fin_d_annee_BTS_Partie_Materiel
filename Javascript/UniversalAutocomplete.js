/**
 * universalAutocomplete.js — Composant d'autocomplete générique
 * (utilisateurs, caisses, types/sous-types/noms de référence).
 *
 * Émet `autoCompleteSelected` (CustomEvent) sur l'input à la sélection.
 */
class UniversalAutocomplete {
  /**
   * @param {string} inputId - ID de l'input HTML cible.
   * @param {"user"|"caisse"|"materiel_type"|"materiel_sous_type"|"materiel_nom"|"materiel_code"} type - Type de données interrogé via searchUniversal.php.
   * @param {(item: object) => void} [onSelectCallback=null] - Callback appelé après sélection d'un item.
   * @param {() => string} [filterCallback=null] - Fonction renvoyant un filtre dynamique (ex: type parent).
   * @param {() => string} [filterSousTypeCallback=null] - Fonction renvoyant un filtre dynamique de sous-type.
   * @param {object} [options={}] - Options additionnelles.
   * @param {boolean} [options.numericOnly] - Restreint la saisie aux chiffres.
   * @param {boolean} [options.strictMode] - Vide le champ au blur si la valeur n'a pas été choisie depuis la liste.
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
    this.isValidSelection = false;

    if (this.input) {
      this.init();
    }
  }

  /**
   * Configure les écouteurs d'événements et crée le conteneur de suggestions.
   *
   * @returns {void}
   */
  init() {
    if (this.options.numericOnly) {
      this.input.addEventListener("keypress", (e) => {
        const charCode = e.which ? e.which : e.keyCode;
        if (charCode > 31 && (charCode < 48 || charCode > 57)) {
          e.preventDefault();
        }
      });
      this.input.addEventListener("input", () => {
        if (this.options.numericOnly) {
          this.input.value = this.input.value.replace(/[^0-9]/g, "");
        }
      });
    }

    const parent = this.input.parentElement;
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

    this.input.addEventListener("input", () => this.handleInput());
    this.input.addEventListener("focus", () => this.handleFocus());

    this.input.addEventListener("blur", () => {
        if (this.options.strictMode && !this.isValidSelection && this.input.value.trim() !== "") {
            setTimeout(() => {
                if (!this.isValidSelection) {
                    this.input.value = "";
                    this.input.dispatchEvent(new Event('input', { bubbles: true }));
                }
            }, 150);
        }
    });

    document.addEventListener("click", (e) => {
      if (e.target !== this.input && e.target !== this.container) {
        this.hide();
      }
    });
  }

  /**
   * Relance la recherche au focus pour réafficher la liste.
   *
   * @returns {void}
   */
  handleFocus() {
    this.search(this.input.value.trim());
  }

  /**
   * Déclenche une recherche debouncée à la saisie.
   *
   * @returns {void}
   */
  handleInput() {
    this.isValidSelection = false;
    clearTimeout(this.debounceTimer);
    const query = this.input.value.trim();

    this.debounceTimer = setTimeout(() => {
      this.search(query);
    }, 200);
  }

  /**
   * Interroge le serveur et affiche les résultats trouvés.
   *
   * @param {string} query - Préfixe de recherche.
   * @returns {Promise<void>}
   */
  async search(query) {
    try {
      let filterVal = "";
      if (this.filterCallback && typeof this.filterCallback === "function") {
        filterVal = this.filterCallback();
      }

      let filterSousTypeVal = "";
      if (this.filterSousTypeCallback && typeof this.filterSousTypeCallback === "function") {
        filterSousTypeVal = this.filterSousTypeCallback();
      }

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
        if (this.options.strictMode && query !== "") {
            this.container.innerHTML = "<div class='p-2 text-sm text-gray-500 italic'>Non trouvé. Utilisez 'Gestion des Références'.</div>";
            this.container.style.display = "block";
        }
      }
    } catch (error) {
      console.error("Erreur UniversalAutocomplete:", error);
    }
  }

  /**
   * Construit le DOM des suggestions.
   *
   * @param {Array<{id:any, label:string, value:string, meta:object}>} items - Items renvoyés par l'API.
   * @returns {void}
   */
  displayResults(items) {
    this.container.innerHTML = "";
    this.container.style.display = "block";

    items.forEach((item) => {
      const div = document.createElement("div");
      div.className = "autocomplete-suggestion";
      div.textContent = item.label;

      div.addEventListener("click", (e) => {
        e.stopPropagation();
        this.select(item);
      });

      this.container.appendChild(div);
    });
  }

  /**
   * Applique la sélection : remplit l'input, masque la liste, déclenche les callbacks.
   *
   * @param {{id:any, label:string, value:string, meta:object}} item - Item sélectionné.
   * @returns {void}
   */
  select(item) {
    this.input.value = item.value;
    this.isValidSelection = true;
    this.hide();

    if (this.onSelectCallback) {
      this.onSelectCallback(item);
    }

    const event = new CustomEvent("autoCompleteSelected", { detail: item });
    this.input.dispatchEvent(event);
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

window.UniversalAutocomplete = UniversalAutocomplete;

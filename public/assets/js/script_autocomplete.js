document.addEventListener('DOMContentLoaded', () => {
    class Autocomplete {
        constructor(inputElement, options = {}) {
            if (!inputElement) {
                console.error("El elemento de entrada no existe.");
                return;
            }
  
            this.inputElement = inputElement;
            this.options = {
                resultsContainer: options.resultsContainer || this.createResultsContainer(),
                resultList: options.resultList || this.createResultList(),
                listItemClass: options.listItemClass || 'autocomplete-item',
                filterFunction: options.filterFunction || this.defaultFilterFunction,
                filterData: options.filterData || [],
                ...options
            };
  
            this.initEvents();
        }
  
        createResultsContainer() {
            const container = document.createElement('div');
            container.classList.add('autocomplete-results');
            if (this.inputElement.parentNode) {
                this.inputElement.parentNode.appendChild(container);
            } else {
                console.error("No se pudo agregar el contenedor de resultados porque el padre del elemento de entrada es nulo.");
            }
            return container;
        }
  
        createResultList() {
            if (!this.options.resultsContainer) {
                console.error("No se pudo crear la lista de resultados porque el contenedor no existe.");
                return null;
            }
  
            const list = document.createElement('ul');
            list.classList.add('autocomplete-list');
            this.options.resultsContainer.appendChild(list);
            return list;
        }
  
        defaultFilterFunction(query) {
            return this.options.filterData.filter(item => item.toLowerCase().includes(query.toLowerCase()));
        }
  
        initEvents() {
            if (this.inputElement) {
                this.inputElement.addEventListener('input', (event) => this.onInput(event));
            }
  
            if (this.options.resultList) {
                this.options.resultList.addEventListener('click', (event) => {
                    if (event.target.tagName === 'LI') {
                        this.onResultClick(event);
                    }
                });
            } else {
                console.warn("No se pudo agregar eventos a la lista de resultados porque no existe.");
            }
        }
  
        onInput(event) {
            const query = event.target.value;
            const results = this.options.filterFunction.call(this, query);
            this.updateResults(results);
        }
  
        updateResults(results) {
            if (!this.options.resultList) {
                console.warn("No se pueden actualizar los resultados porque la lista de resultados no existe.");
                return;
            }
  
            this.options.resultList.innerHTML = '';
            results.forEach(result => {
                const listItem = document.createElement('li');
                listItem.classList.add(this.options.listItemClass);
                listItem.textContent = result;
                this.options.resultList.appendChild(listItem);
            });
        }
  
        onResultClick(event) {
            this.inputElement.value = event.target.textContent;
            this.clearResults();
        }
  
        clearResults() {
            if (this.options.resultList) {
                this.options.resultList.innerHTML = '';
            }
        }
    }
  
    // Uso de la clase Autocomplete
    const grados = [
        "Alcaide", "Subalcaide", "Adjutor Principal", "Adjutor", "Subadjutor",
        "Ayudante Mayor", "Ayudante Principal", "Ayudante de Segunda",
        "Ayudante de Tercera", "Ayudante de Cuarta", "Ayudante de Quinta",
        "Subayudante"
    ];
    const tipos_servicios = [
        "Alta de Usuario", "Blanqueo de Contraseña", "Reparación de PC", "Reparación de Impresora",
        "Problema de Software", "Problema de Hardware", "Problema de Red"
    ];
  
    const tipoServicioInput = document.getElementById('autocomplete-input-tipo-servicio');
    const tipoServicioResultsContainer = document.querySelector('#autocomplete-input-tipo-servicio + .autocomplete-results-container');
    const tipoServicioResultList = tipoServicioResultsContainer?.querySelector('.autocomplete-list');
  
    if (tipoServicioInput && tipoServicioResultsContainer && tipoServicioResultList) {
        new Autocomplete(tipoServicioInput, {
            resultsContainer: tipoServicioResultsContainer,
            resultList: tipoServicioResultList,
            filterData: tipos_servicios
        });
    } else {
        console.warn("No se encontró uno o más elementos necesarios para el Autocomplete de tipo de servicio.");
    }
  
    const gradoInput = document.getElementById('autocomplete-input-grado');
    const gradoResultsContainer = document.querySelector('#autocomplete-input-grado + .autocomplete-results-container');
    const gradoResultList = gradoResultsContainer?.querySelector('.autocomplete-list');
  
    if (gradoInput && gradoResultsContainer && gradoResultList) {
        new Autocomplete(gradoInput, {
            resultsContainer: gradoResultsContainer,
            resultList: gradoResultList,
            filterData: grados
        });
    } else {
        console.warn("No se encontró uno o más elementos necesarios para el Autocomplete de grado.");
    }
  });
  